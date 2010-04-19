<?php

require_once PATH_API .'AbstractDataObject.class.php';
require_once PATH_API .'DataObject.class.php';
require_once PATH_DB .'DB.class.php';

class PageObject extends AbstractDataObject {

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
	public function __construct($id = null) {
		parent::__construct();

		if ($id)
			$this->get($id);
	}

	/**
	 * get a page from the db and set it as internal representation for this
	 * object
	 *
	 * @param $id int primary key id of the desired page 
	 *
	 * @throws Exception on error
	 */
	public function get($id = null) {
		if (!$id || !is_numeric($id))
			throw new Exception('Invalid parameter passed: '. var_export($id, true));

		$db = DB::factory(DB_APP);

		$q = new Query("SELECT * FROM content_pages WHERE id = $$0");
		$row = $db->selectOne($q->addArgument($id));

		if (!$row)
			throw new Exception('Page not found: '. $id);

		$this->id = $id;
		$this->data = $row;

		$q = new Query("SELECT * FROM content_page_objects WHERE page_id = $$0 ORDER BY ordinal");
		$objects = $db->select($q->addArgument($id));

		foreach ($objects as &$v)
			$v = new DataObject($v['object_id'], $this->data['locale_id'], $v['version']);

		$this->data['objects'] = $objects;
	}

	/**
	 * Convert the internal data to arrays
	 *
	 * @see parent::toArray()
	 *
	 * @returns array
	 */
	public function toArray(array $res = null) {
		$res = parent::toArray($res);

		foreach ($res['objects'] as &$v)
			if ($v instanceof AbstractDataObject)
				$v = $v->toArray();

		return $res;
	}
}

?>
