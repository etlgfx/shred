<?php

require_once PATH_API .'AbstractDataObject.class.php';

class DataContents extends AbstractDataObject {

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
	public function __construct($id = null, $locale_id = null, $version = null) {
		parent::__construct();

		if ($id && $locale_id)
			$this->get($id, $locale_id, $version);
	}

	/**
	 * get a page from the db and set it as internal representation for this
	 * object
	 *
	 * @param $id int primary key id of the desired object 
	 * @param $locale_id int
	 * @param $version int or null, if null is supplied the latest version of the object is fetched
	 *
	 * @throws Exception on error
	 */
	public function get($id, $locale_id, $version = null) {
		if (!$id || !is_numeric($id))
			throw new Exception('Invalid parameter passed id: '. var_export($id, true));
		if (!$locale_id || !is_numeric($locale_id))
			throw new Exception('Invalid parameter passed locale_id: '. var_export($locale_id, true));

		$db = DB::factory(DB_APP);

		$q = new Query('
			SELECT *
			FROM data_contents
			WHERE object_id = $$0
				AND locale_id = $$1
				AND status = '. self::OBJ_STATUS_ACTIVE .
				($version ? ' version = $$2 ' : ' ORDER BY version DESC ') .'
			LIMIT 1');

		$this->data = $db->selectOne($q->addArgument(array(0 => $id, 1 => $locale_id, 2 => $version)));
		$this->id = $this->data['id'];
	}

	/**
	 * @returns int thumb_id if set
	 */
	public function getThumbId() {
		return $this->data['thumb_id'];
	}

	public function deleteImpl() {
	}
}

?>
