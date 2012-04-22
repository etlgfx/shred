<?php

/**
 * @class ImageAPI defines an interface between controllers and the Image
 * classes and the DB
 *
 * @see Image
 * @see AbstractImage
 */
class ImageAPI {
	const FLAG_GET_DEFAULT = 0xff;
	const FLAG_GET_TAGS = 0x01;
	const FLAG_GET_NOTHING = 0x00;

	/**
	 * list of image sizes to generate along with which method to use
	 * 
	 * @see saveImageSizes()
	 */
	private static $sizes = array(
		'raw' => array(),
		'1920' => array(
			'x' => 1920,
			'y' => 1080,
			'method' => 'Max'),
		'800' => array(
			'x' => 800,
			'y' => 800,
			'method' => 'Max'),
		'600' => array(
			'x' => 600,
			'y' => 600,
			'method' => 'Max' /*Crop'*/),
		'300' => array(
			'x' => 300,
			'y' => 300,
			'method' => 'Max' /*Crop'*/),
		'300wide' => array(
			'x' => 300,
			'y' => 100,
			'method' => 'CenterCrop' /*Crop'*/),
		'thumb' => array(
			'x' => 128,
			'y' => 128,
			'method' => 'Max' /*Crop'*/),
		'square' => array(
			'x' => 64,
			'y' => 64,
			'method' => 'CenterCrop'),
		);

	/**
	 * Initialize DB handle, and dependent APIs
	 */
	public function __construct() {
		$this->db = DB::factory(DB_ETLGFX);
		$this->tag = new TagAPI();
		$this->image = AbstractImage::factory();
	}

	/**
	 * Create a new image or edit an existing one, depending if the image_id
	 * parameter in the data array is set
	 *
	 * @param [in] $user_id int
	 * @param [in] $data form data array
	 *
	 * @returns bool
	 */
	public function edit($user_id, array $data) {
		if ($this->image->open($data['image']['tmp_name'])) {

			$extension = Util::getFileExtension($data['image']['name']);

			if (isset($data['image_id']) && is_numeric($data['image_id']))
				$current_row = $this->getImageRaw($data['image_id']);
			else
				$current_row = $this->newImageRow($user_id);

			$this->getImageForm()->addHidden('image_id', $current_row['image_id']);

			$image_id = $current_row['image_id'];
			$hash = $current_row['hash'];

			if (!$this->saveImageSizes($image_id, $hash, $extension)) {
				return false;
			}

			$this->saveImage($image_id, $extension, $data);
			Request::instance()->pushData('image', $image_id);
		}

		return true;
	}

	/**
	 * @param [in] $image_id int
	 *
	 * @returns array
	 */
	private function getImageRaw($image_id) {
		$q = new Query("SELECT * FROM images WHERE image_id = $$0 LIMIT 1");
		$q->addArgument($image_id);

		$row = $this->db->selectOne($q);
		$row['image_id'] = (int)$row['image_id'];

		return $row;
	}

	/**
	 * generate a random hash for a new image
	 *
	 * @param [in] $user_id int
	 *
	 * @returns string 36 char hash
	 */
	private static function generateHash($user_id) {
		return Util::encodePassword($user_id . time() . rand(0, 65535));
	}

	/**
	 * Add a new placeholder record to the images table
	 *
	 * @param [in] $user_id int
	 *
	 * @returns array('image_id' => int, 'hash' => string)
	 */
	private function newImageRow($user_id) {
		$q = new Query('INSERT INTO images SET
			user_id = $$0,
			create_ts = $$1,
			hash = $$2,
			perm = 0');
		$hash = self::generateHash($user_id);
		$q->addArgument(array(
				0 => $user_id,
				1 => time(),
				2 => $hash));

		$this->db->query($q);
		$id = $this->db->insertID();

		return array('image_id' => (int)$id, 'hash' => $hash);
	}

	/**
	 * loop through the sizes array and generate each image size
	 * 
	 * @see $sizes
	 *
	 * @param [in] $image_id int
	 * @param [in] $hash string
	 * @param [in] $extension string - extension of the original image
	 *
	 * @returns bool
	 */
	private function saveImageSizes($image_id, $hash, $extension) {
		foreach (self::$sizes as $id => $size) {
			$path = $this->generateImagePath($id, $image_id, $hash);
			$dir = dirname($path);

			if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
				Log::raiseError('Unable to write files, is the content directory writable? '. PATH_CONTENT .'images/ -> '. $dir, Log::USER_ERROR);
				return false;
			}

			if (!isset($size['x'], $size['y'], $size['method'])) {
				if (!$this->image->copy($path .'.'. $extension))
					return false;
			}
			else if (isset($size['method']) && method_exists($this, 'transform'. $size['method'])) {
				if ($this->image->getWidth() < $size['x'] && $this->image->getHeight() < $size['y'])
					continue;

				$this->{'transform'. $size['method']}($size['x'], $size['y'], $path .'.jpg');
			}
			else
				throw new Exception('Bad image size config: '. var_export($size, true));
		}

		return true;
	}

	/**
	 * callback method for saveImageSizes, this method scales to fit inside the given dimensions
	 *
	 * @see saveImageSizes()
	 *
	 * @param [in] $x int - width
	 * @param [in] $y int - height
	 * @param [in] $path string file path to save to, including extension
	 */
	private function transformMax($x, $y, $path) {
		$this->image->resize($x, $y);
		$this->image->write($path);
	}

	/**
	 * callback method for saveImageSizes, this method scales to fit inside the
	 * given dimensions, but crops to try to match the aspect ratio within
	 * a 2:1 range.
	 *
	 * So if the image is 4 times as tall, it'll crop it to be 2 times as tall
	 * as the target aspect ratio
	 *
	 * @see saveImageSizes()
	 *
	 * @param [in] $x int - width
	 * @param [in] $y int - height
	 * @param [in] $path string file path to save to, including extension
	 */
	private function transformMaxCrop($x, $y, $path) {
	}

	/**
	 * callback method for saveImageSizes, this method scales to fit inside the given dimensions
	 *
	 * @see saveImageSizes()
	 *
	 * @param [in] $x int - width
	 * @param [in] $y int - height
	 * @param [in] $path string file path to save to, including extension
	 */
	private function transformCenterCrop($x, $y, $path) {
		$this->image->cropAspect($x, $y);
		$this->image->write($path);
	}

	/**
	 * save row to db, and save tags
	 *
	 * @param [in] $image_id int
	 * @param [in] $extension string
	 * @param [in] $data array of post data
	 *
	 * @return bool
	 */
	private function saveImage($image_id, $extension, $data) {
		$q = new Query("UPDATE images SET
			extension = $$1,
			width = $$2,
			height = $$3,
			update_ts = $$4,
			title = $$5, 
			description = $$6,
			perm = $$7,
			gallery_id = $$8
			WHERE image_id = $$0");

		$q->addArgument(array(
				0 => $image_id,
				1 => $extension,
				2 => $this->image->getWidth(),
				3 => $this->image->getHeight(),
				4 => time(),
				5 => $data['title'],
				6 => $data['description'],
				7 => $data['perm'],
				8 => $data['gallery_id'] ? $data['gallery_id'] : null
				));

		if ($this->db->query($q))
			return $this->tag->sync($data['tags'], TagAPI::REMOTE_IMAGE, $image_id);
		else
			return false;
	}

	/**
	 * generate last few parts of the image path which is reusable across sizes
	 *
	 * @param [in] $image_id int
	 * @param [in] $hash string
	 *
	 * @returns string relative file path part
	 */
	public static function generateImagePathTail($image_id, $hash) {
		return substr($hash, 0, 3) .'/'. substr($hash, 3, 3) .'/'.
			$hash .'_'. sprintf("%08d", $image_id);
	}

	/**
	 * generate the full path to the image on the FS
	 *
	 * @param [in] $size_id string
	 * @param [in] $image_id int
	 * @param [in] $hash string
	 *
	 * @returns string file path
	 */
	private function generateImagePath($size_id, $image_id, $hash) {
		return PATH_CONTENT .'images/'.
			$size_id .'/'. self::generateImagePathTail($image_id, $hash);
	}

	/**
	 * @param [in] $id int - id of the image we're editing to prepopulate the * form
	 *
	 * @returns Form object
	 */
	public function getImageForm($id = null) {//TODO use $id param
		if (!isset($this->form)) {
			if (is_numeric($id) && $id) { //TODO i don't like this
				$id = (int)$id;

				$row = $this->getImageRaw($id);
				$tags = $this->tag->getTags(TagAPI::REMOTE_IMAGE, $id);
				$row['tags'] = implode(', ', $tags);
			}

			$this->form = new Form();

			$this->form->addDescriptor('image', Form::TYPE_IMAGE, true, array($this->image, 'validImage'));
			$this->form->setDisplay('image', 'Image', 'JPEG, GIF, PNG, etc');

			$this->form->addDescriptor('title', Form::TYPE_TEXT, false);
			$this->form->setDisplay('title', 'Title');

			$this->form->addDescriptor('description', Form::TYPE_TEXTBOX, false);
			$this->form->setDisplay('description', 'Description');

			$this->form->addDescriptor('tags', Form::TYPE_TEXT);
			$this->form->setDisplay('tags', 'Tags');

			$this->form->addSelect('perm', array(
					PERMISS_BITMASK_DEFAULT => 'Everyone',
					PERMISS_BITMASK_USERS => 'Only Registered Users',
					PERMISS_BITMASK_SUPER => 'Only SUPER Users'));
			$this->form->setDisplay('perm', 'Permissions');

			$gallery = new GalleryAPI();

			$this->form->addSelect('gallery_id', $gallery->getGalleryList(), false);
			$this->form->setDisplay('gallery_id', 'Add to Gallery');

			if ($id) {
				$this->form->validate($row, array('title', 'description', 'tags', 'perm'));
				$this->form->addHidden('image_id', $id);

				Request::instance()->pushData('image', $id, $this->createImageObj($row));
			}
		}

		return $this->form;
	}


	/**
	 * Fetch an image
	 *
	 * @param [in] $id int
	 * @param [in] $push bool - whether to push the image's user_id to datastore for post processing
	 *
	 * @see Image
	 *
	 * @returns Image
	 */
	public function getImage($id, $push = true) {
		return $this->createImageObj($this->getImageRaw($id), $push);
	}

	/**
	 * Fetch a set of image meta data by id
	 *
	 * @param [in] $ids array of integers
	 *
	 * @see Image
	 *
	 * @returns array of Image objects
	 */
	public function getImages(array $ids) {
		$return = array();
		foreach ($ids as $id)
			$return[$id] = $this->getImage($id);

		return $return;
	}

	/**
	 * Fetch most recent images
	 *
	 * @returns array of Image objects
	 */
	public function getNewImages() {
		$images = $this->db->select(new Query("SELECT * FROM images ORDER BY update_ts DESC LIMIT 20"));

		foreach ($images as $k => $v)
			$images[$k] = $this->createImageObj($v);

		return $images;
	}

	/**
	 * Fetch images by user ID
	 *
	 * @param [in] $user_id int
	 *
	 * @returns array of Image objects
	 */
	public function getUserImages($user_id) {
		$q = new Query("SELECT * FROM images
			WHERE user_id = $$0 ORDER BY update_ts DESC");

		$q->addArgument($user_id);

		$images = $this->db->select($q);

		foreach ($images as $k => $v)
			$images[$k] = $this->createImageObj($v);

		return $images;
	}

	/**
	 * creates an Image object, and fills in the tags and pushes the user_id to
	 * the datastore
	 *
	 * @param [in] $data array of image db row
	 * @param [in] $push boolean if true, push data to datastore for post processing
	 *
	 * @returns Image
	 */
	private function createImageObj(array $data, $push = true, $flags = self::FLAG_GET_DEFAULT) {
		$image = new Image($data);

		if ($flags & self::FLAG_GET_TAGS)
			$image->setTags($this->tag->getTags(TagAPI::REMOTE_IMAGE, $image->getImageId()));

		if ($push) {
			$request = Request::instance();
			$request->pushData('user', $image->getUserId());
		}

		return $image;
	}

	public function getNextImage(Image $obj) {
		$q = new Query("SELECT * FROM images WHERE gallery_id = $$0 and image_id < $$1 ORDER BY image_id DESC LIMIT 1");
		$q->addArgument(array(
				0 => $obj->getGalleryId(),
				1 => $obj->getImageId()));

		$res = $this->db->selectOne($q);

		return $res ? $this->createImageObj($res, false, self::FLAG_GET_NOTHING) : null;
	}

	public function getPreviousImage(Image $obj) {
		$q = new Query("SELECT * FROM images WHERE gallery_id = $$0 and image_id > $$1 ORDER BY image_id ASC LIMIT 1");
		$q->addArgument(array(
				0 => $obj->getGalleryId(),
				1 => $obj->getImageId()));

		$res = $this->db->selectOne($q);

		return $res ? $this->createImageObj($res, false, self::FLAG_GET_NOTHING) : null;
	}
}
