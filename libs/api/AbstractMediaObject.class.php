<?php

require_once PATH_API .'AbstractDataObject.class.php';

class AbstractMediaObject extends AbstractDataObject {

	protected $relational_table, $table, $external_id;

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
			$this->getById($id);
	}

	/**
	 * get a page from the db and set it as internal representation for this
	 * object
	 *
	 * @param $id int primary key id of the desired page 
	 *
	 * @throws Exception on error
	 */
	public function getById($id = null) {
		if (!$id || !is_numeric($id))
			throw new Exception('Invalid parameter passed: '. var_export($id, true));

		$db = DB::factory(DB_APP);

		if (!$this->table)
			throw new Exception("Badly configured Media Object class: ". get_class($this));

		$this->id = (int)$id;
		$q = new Query("SELECT * FROM ". $this->table ." WHERE id = $$0");
		$this->data = $db->selectOne($q->addArgument($id));
	}

	public function getByContentType($content_id, $field_type_name) {
		$db = DB::factory(DB_APP);

		if (!$this->relational_table || !$this->external_id)
			throw new Exception("Badly configured Media Object class: ". $class);

		$q = new Query("SELECT ". $this->external_id ." FROM ". $this->relational_table ." WHERE content_id = $$0 AND field_type_name = $$1");
		$row = $db->selectOne($q->addArgument(array(0 => $content_id, 1 => $field_type_name)));

		return $this->getById($row[$this->external_id]);
	}

	public function deleteImpl() {
	}
}

?>
