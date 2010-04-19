<?php

require_once PATH_API .'AbstractDataObject.class.php';
require_once PATH_API .'DataContents.class.php';
require_once PATH_API .'ImageObject.class.php';
require_once PATH_API .'ContentType.class.php';

class DataObject extends AbstractDataObject {

	/**
	 * constructor
	 *
	 * @param $id null or int, if int is giving the object will attempt to
	 * initialize with the value of the object fetched from the datasource / db
	 *
	 * @see get()
	 *
	 * @throws Exception on failure to initialize
	 */
	public function __construct($id = null, $locale_id = null, $version = null, $summary = false) {
		parent::__construct();

		if ($id && $locale_id)
			$this->get($id, $locale_id, $version, $summary);
	}

	/**
	 * get an object from the db and set it as internal representation for this
	 * object
	 *
	 * @param $id int primary key id of the desired object 
	 * @param $locale_id int
	 * @param $version int or null, if null is supplied the latest version of the object is fetched
	 *
	 * @throws Exception on error
	 */
	public function get($id, $locale_id, $version = null, $summary = false) {
		if (!$id || !is_numeric($id))
			throw new Exception('Invalid parameter passed id: '. var_export($id, true));
		if (!$locale_id || !is_numeric($locale_id))
			throw new Exception('Invalid parameter passed locale_id: '. var_export($locale_id, true));

		$db = DB::factory(DB_APP);

		$q = new Query("SELECT * FROM data_objects WHERE id = $$0 AND status = ". self::OBJ_STATUS_ACTIVE);
		$row = $db->selectOne($q->addArgument($id));
		$this->id = $id;

		if (!$row)
			return null;

		$this->data = $row;
		$this->data['contents'] = new DataContents($id, $locale_id, $version);

		if ($summary) {
			$image = new ImageObject($this->data['contents']->getThumbId());
			$this->data['media'] = array('thumb' => $image);
		}
		else {
			$type = new ContentType($row['type_id'], false);
			$this->data['content-type'] = $type;
			$this->data['media'] = array();

			foreach ($type->getFields() as $field) {
				$field_type_name = $field['field_type_name'];

				$obj = null;
				switch ($field['params']['type']) {
					case 'image':
						$obj = new ImageObject();
						break;
					case 'audio':
						$obj = new AudioObject();
						break;
					case 'video':
						$obj = new VideoObject();
						break;
					case 'file':
						$obj = new FileObject();
						break;
					default:
						//TODO exception? unknown type
						throw new Exception('Unknown type contained in object: '. $field['params']['type']);
				}

				$obj->getByContentType($this->data['contents']->getId(), $field_type_name);
				$this->data['media'][$field_type_name] = $obj;
			}
		}
	}

	public function getByAccountId($account_id) {
		if (!$account_id || !is_numeric($account_id))
			throw new Exception('Invalid parameter passed account_id: '. var_export($account_id, true));

		$db = DB::factory(DB_APP);

		$q = new Query('SELECT data_objects.id as `data_objects.id`,
				data_objects.created_ts as `data_objects.created_ts`,
				data_objects.updated_ts as `data_objects.updated_ts`,
				data_objects.status as `data_objects.status`,
				data_contents.id as `data_contents.id`,
				data_contents.object_id as `data_contents.object_id`,
				data_contents.locale_id as `data_contents.locale_id`,
				data_contents.created_ts as `data_contents.created_ts`,
				data_contents.updated_ts as `data_contents.updated_ts`,
				data_contents.title as `data_contents.title`,
				data_contents.description as `data_contents.description`,
				data_contents.thumb_id as `data_contents.thumb_id`,
				data_contents.ordinal as `data_contents.ordinal`,
				data_contents.status as `data_contents.status`,
				data_contents.version as `data_contents.version`,
				locales.id as `locales.id`,
				locales.account_id as `locales.account_id`,
				locales.name as `locales.name`,
				locales.description as `locales.description`,
				locales.status as `locales.status`,
				locales.created_ts as `locales.created_ts`,
				locales.updated_ts as `locales.updated_ts`,
				content_types.id as `content_types.id`,
				content_types.account_id as `content_types.account_id`,
				content_types.short_name as `content_types.short_name`,
				content_types.name as `content_types.name`,
				content_types.description as `content_types.description`,
				content_types.status as `content_types.status`,
				content_types.created_ts as `content_types.created_ts`,
				content_types.updated_ts as `content_types.updated_ts`
			FROM data_objects JOIN
				data_contents ON (data_contents.object_id = data_objects.id) JOIN
				locales ON (locales.id = data_contents.locale_id) JOIN
				content_types ON (content_types.id = data_objects.type_id)
			WHERE locales.account_id = $$0');

		return $db->select($q->addArgument($account_id), true);
	}

	/**
	 * Convert the internal data to arrays
	 *
	 * @see parent::toArray()
	 *
	 * @returns array
	 */
	public function toArray(array $res = null) {
		$res = parent::toArray();

		foreach ($res['media'] as &$v)
			if ($v instanceof AbstractDataObject)
				$v = $v->toArray();

		return $res;
	}

	public function deleteImpl() {
	}
}

?>
