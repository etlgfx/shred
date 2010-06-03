<?php

/**
 * @class AbstractImage defines a common interface and several common methods
 * for opening, validating, resizing and processing images
 */
abstract class AbstractImage {
	protected $width, $height;
	protected $filename;
	protected $filesize;
	protected $aspect;
	protected $image_open;
	protected $mime;

	/**
	 * Priority based set of Image handling class suffixes, the factory method
	 * uses these to return a sensible image handler
	 *
	 * @see factory()
	 */
	private static $types = array(
		'imagick' => 'ImageMagick',
		'gmagick' => 'GraphicsMagick',
		'gd' => 'GD',
		);

	/**
	 * init properties to null
	 */
	public function __construct() {
		$this->width = $this->height = $this->filename = $this->aspect = $this->image_open = $this->filesize = $this->mime = null;
	}

	/**
	 * close any open image resources and reset the image_open variable
	 */
	public function __destruct() {
		$this->close();
		$this->image_open = null;
	}

	/**
	 * factory method to return an image handler class
	 *
	 * @see $types
	 *
	 * @param [in] $type string - request type, if null a class will be returned based on priority
	 *
	 * @returns concrete AbstractImage subclass
	 */
	public final static function factory($type = null) {
		static $obj = null;

		if ($obj === null) {
			if (is_string($type) && isset(self::$types[$type])) {
				$class = self::className(self::$types[$type]);
				$path = self::path($class);

				if (file_exists($path)) {
					require_once $path;
					$obj = new $class();
				}
			}

			if (!$obj) {
				foreach (self::$types as $k => $t) {
					if ($obj)
						break;

					if ($k == $type)
						continue;

					$class = self::className($t);
					$path = self::path($class);

					if (file_exists($path)) {
						require_once $path;
						$obj = new $class();
					}
				}
			}
		}

		if (!$obj)
			throw new Exception('Unable to instantiate Image Processing class');

		return $obj;
	}

	/**
	 * get the class name for the requested Image handler type
	 *
	 * @returns string
	 */
	private static function className($type) {
		return 'Image'. $type;
	}

	/**
	 * get the path for the requested Image handler type
	 *
	 * @returns string - absolute filesystem path
	 */
	private static function path($class) {
		return PATH_LIBS .'media/'. $class .'.class.php';
	}

	/**
	 * forces subclasses to implement a validation method
	 *
	 * @param [in] $data array $_FILES element for the image in question
	 *
	 * @returns bool
	 */
	public abstract function validImage(array $data = null);

	/**
	 * Open the image and set a bunch of standard properties
	 *
	 * @param [in] $filename string file path
	 *
	 * @returns bool
	 */
	public final function open($filename) {
		if (file_exists($filename) && $this->openImpl($filename)) {
			$this->filesize = filesize($filename);
			$this->filename = $filename;
			$this->image_open = true;
			$this->aspect = $this->width / $this->height;

			$this->mime = $this->extractMimeType();
			return true;
		}
		else {
			Error::raise('Can\'t open file: '. $filename .'; file does not exist');
			return false;
		}
	}

	/**
	 * Use the Fileinfo standard PHP 5.3 library to extract mime type from the
	 * file
	 *
	 * @returns string
	 */
	protected function extractMimeType() {
		$f = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($f, $this->filename);
		finfo_close($f);

		return $mime;
	}

	/**
	 * @param [in] $filename string file path
	 *
	 * @returns bool
	 */
	protected abstract function openImpl($filename);

	/**
	 * Do an OS level copy of the currently opened image, instead of re-saving lossy images
	 *
	 * @param [in] $dest string path - destination filename, source filename is already in the object at this point
	 *
	 * @returns bool
	 */
	public final function copy($dest) {
		if ($this->image_open && $this->filename && file_exists($this->filename))
			return copy($this->filename, $dest);

		return false;
	}

	/**
	 * Write the image with any existing transformations
	 *
	 * @param [in] $filename string target file path
	 *
	 * @returns bool
	 */
	public final function write($filename) {
		return $this->writeImpl($filename);
	}

	/**
	 * Write the image with any existing transformations
	 *
	 * @param [in] $filename string target file path
	 *
	 * @returns bool
	 */
	protected abstract function writeImpl($filename);

	/**
	 * Close the image, clean up
	 */
	public abstract function close();

	/**
	 * Resize the image with aspect ratio, images max dimensions will be the
	 * given ones (i.e. same size or smaller than dimensions given)
	 * This method calculates the corrected scaled sizes and passes them on to resizeImpl
	 *
	 * @see resizeImpl()
	 *
	 * @param [in] $w int
	 * @param [in] $h int
	 *
	 * @returns bool
	 */
	public final function resize($w, $h) {
		if (is_int($w) && is_int($h) && $w > 0 && $h > 0) {
			if ($this->width < $w && $this->height < $h)
				return false;

			$s_ar = $this->aspect;
			$d_ar = $w / $h;

			if ($s_ar == 0 || $d_ar == 0)
				return false;
			else if ($s_ar > $d_ar)
				$h = (int)round($w / $s_ar);
			else if ($s_ar < $d_ar)
				$w = (int)round($h * $s_ar);

			return $this->resizeImpl($w, $h);
		}

		return false;
	}

	/**
	 * Resize the image to the given dimensions
	 *
	 * @see resize()
	 *
	 * @param [in] $w int
	 * @param [in] $h int
	 *
	 * @returns bool
	 */
	protected abstract function resizeImpl($w, $h);

	/**
	 * Crop the image to the given dimensions
	 *
	 * @see cropImpl()
	 *
	 * @param [in] $l int - left bound; offset from origin
	 * @param [in] $t int - top bound; offset from origin
	 * @param [in] $r int - right bound; offset from origin
	 * @param [in] $b int - bottom bound; offset from origin
	 *
	 * @returns bool
	 */
	public final function crop($l, $t, $r, $b) {
		if (is_int($l) && is_int($t) && is_int($r) && is_int($b) &&
			$l >= 0 && $t >= 0 && $r >= $l && $b >= $t)
			return $this->cropImpl($l, $t, $r, $b);

		return false;
	}

	/**
	 * Crop the image to the given dimensions
	 *
	 * @see crop()
	 *
	 * @param [in] $l int - left bound; offset from origin
	 * @param [in] $t int - top bound; offset from origin
	 * @param [in] $r int - right bound; offset from origin
	 * @param [in] $b int - bottom bound; offset from origin
	 *
	 * @returns bool
	 */
	protected abstract function cropImpl($l, $t, $r, $b);

	/**
	 * Resize and crop the image in one swoop, the gained efficiency of this method depends on the library used
	 *
	 * @see resizeCropImpl()
	 *
	 * @param [in] $l int - left bound; offset from origin
	 * @param [in] $t int - top bound; offset from origin
	 * @param [in] $r int - right bound; offset from origin
	 * @param [in] $b int - bottom bound; offset from origin
	 * @param [in] $w int - width of target
	 * @param [in] $h int - height of target
	 *
	 * @returns bool
	 */
	public final function resizeCrop($l, $t, $r, $b, $w, $h) {
		if (is_int($l) && is_int($t) && $l >= 0 && $t >= 0 &&
			is_int($r) && is_int($b) && $r >= 0 && $b >= 0 &&
			is_int($w) && is_int($h) && $w >= 0 && $h >= 0)
			return $this->resizeCropImpl($l, $t, $r, $b, $w, $h);

		return false;
	}

	/**
	 * Resize and crop the image in one swoop, the gained efficiency of this method depends on the library used
	 *
	 * @see resizeCrop()
	 *
	 * @param [in] $l int - left bound; offset from origin
	 * @param [in] $t int - top bound; offset from origin
	 * @param [in] $r int - right bound; offset from origin
	 * @param [in] $b int - bottom bound; offset from origin
	 * @param [in] $w int - width of target
	 * @param [in] $h int - height of target
	 *
	 * @returns bool
	 */
	protected abstract function resizeCropImpl($l, $t, $r, $b, $w, $h);

	/**
	 * Crop the image to the given aspect ratio
	 *
	 * If passed one parameter it is assumed this parameter is the aspect
	 * ration (w / h) desired, and the method will call the crop() method using
	 * the calculated bounds
	 *
	 * If passed two parameters it is assumed these are the exact target
	 * dimensions, the resizeCrop() method will be called using the calculated
	 * bounds to crop to the center of the image as much as possible
	 *
	 * @see crop()
	 * @see resizeCrop()
	 *
	 * @returns bool
	 */
	public final function cropAspect() {
		$args = func_get_args();
		$ar = null;

		if (count($args) == 1 && is_numeric($args[0])) {
			$ar = $args[0];
		}
		else if (count($args) == 2 && is_int($args[0]) && is_int($args[1]) && $args[0] > 0 && $args[1] > 0) {
			$ar = (float)$args[0] / $args[1];

			if ($this->width <= $args[0] && $this->height <= $args[1])
				return true; // nothing to be done, return
		}
		else {
			throw new Exception("Invalid arguments passed to ". __CLASS__ .'::'. __FUNCTION__ .': '. var_export($args, true));
		}

		if (!$this->image_open || !$this->aspect) {
			throw new Exception(__CLASS__ .'::'. __FUNCTION__ ." - Image not initialized, open an image first ");
		}

		if ($ar > $this->aspect) {
			$h = (int)round($this->width / $ar);
			$ho = (int)round(($this->height - $h) * 0.5);

			if (count($args) == 2)
				return $this->resizeCrop(0, $ho, $this->width, $ho + $h, $args[0], $args[1]);
			else
				return $this->crop(0, $ho, $this->width, $ho + $h);
		}
		else {
			$w = (int)round($this->height * $ar);
			$wo = (int)round(($this->width - $w) * 0.5);

			if (count($args) == 2)
				return $this->resizeCrop($wo, 0, $wo + $w, $this->height, $args[0], $args[1]);
			else
				return $this->crop($wo, 0, $wo + $w, $this->height);
		}
	}

	/**
	 * @returns int - null if no image open
	 */
	public function getWidth() {
		return $this->image_open ? $this->width : null;
	}

	/**
	 * @returns int - null if no image open
	 */
	public function getHeight() {
		return $this->image_open ? $this->height : null;
	}

	/**
	 * @returns int - null if no image open
	 */
	public function getSize() {
		return $this->filesize ? $this->filesize : null;
	}

	/**
	 * @returns string - null if no image open
	 */
	public function getFilename() {
		return $this->filename ? $this->filename : null;
	}

	/**
	 * @returns string - null if no image open
	 */
	public function getMimeType() {
		return $this->mime ? $this->mime : null;
	}
}
