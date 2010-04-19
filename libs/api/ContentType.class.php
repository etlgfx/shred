<?php

require_once PATH_API .'AbstractDataObject.class.php';

/** @class ContentType
 *
 * Describes the number and type of attachments that are expected to be
 * associated with an object of this type
 */
class ContentType extends AbstractDataObject {

	/**
	 * constructor
	 *
	 * @param $id null or int, if int is giving the object will attempt to
	 * initialize with the value of the content type fetched from the
	 * datasource / db
	 *
	 * @see get()
	 *
	 * @throws Exception on failure to initialize
	 */
	public function __construct($id = null, $summary = false) {
		parent::__construct();

		if ($id)
			$this->get($id, $summary);
	}

	/**
	 * Fetch a content type from the database and set it as the current
	 * instance
	 *
	 * @param $id int primary key
	 * @param $summary boolean, if true the content type meta data won't be
	 *        fetched, only the field information
	 */
	public function get($id, $summary = false) {
		if (!$id || !is_numeric($id))
			throw new Exception('Invalid parameter passed id: '. var_export($id, true));

		$db = DB::factory(DB_APP);

		$this->id = $id;
		$this->data = array();

		if (!$summary) {
			$q = new Query('SELECT * FROM content_types WHERE id = $$0');
			$this->data = $db->selectOne($q->addArgument($id));
		}

		$q = new Query('SELECT * FROM content_fields WHERE type_id = $$0');
		$res = $db->query($q->addArgument($id));

		$this->data['fields'] = array();
		while ($row = $res->nextAssoc())
			$this->data['fields'][$row['field_type_name']] = $row;

		foreach ($this->data['fields'] as &$field)
			$field['params'] = json_decode($field['params'], true);
	}

	/**
	 * Get the content type field descriptors associated with this content type
	 *
	 * @returns array keys are field_type_name values are arrays describing the
	 *          attachment type
	 */
	public function getFields() {
		return $this->data['fields'];
	}

	/**
	 * Convert the internal data to a pretty associative array, including the
	 * nested child fields
	 *
	 * @see parent::toArray()
	 *
	 * @returns array
	 */
	public function toArray(array $res = null) {
		$ret = parent::toArray($res);

		if (isset($ret['fields']))
			foreach ($ret['fields'] as &$v)
				$v = parent::toArray($v);

		return $ret;
	}

	public function getByAccountId($account_id) {
		if (!$account_id || !is_numeric($account_id))
			throw new Exception('Invalid parameter passed account_id: '. var_export($account_id, true));

		$db = DB::factory(DB_APP);

		$return = array();

		$q = new Query('SELECT * FROM content_types WHERE account_id = $$0');
		$res = $db->query($q->addArgument($account_id));

		while ($row = $res->nextAssoc()) {
			$type = new ContentType();
			$type->id = (int)$row['id'];
			$type->data = $row;
			$return []= $type;
		}

		return $return;
	}

	protected function deleteImpl() {
		die('delete '. $this->id);
	}
}

?>
