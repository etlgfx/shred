<?php

require_once PATH_API .'AbstractDataObject.class.php';

class Locale extends AbstractDataObject {

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
	 * get a locale from the db and set it as internal representation for this
	 * object
	 *
	 * @param $id int primary key id of the desired object 
	 * @param $locale_id int
	 * @param $version int or null, if null is supplied the latest version of the object is fetched
	 *
	 * @throws Exception on error
	 */
	public function get($id) {
		if (!$id || !is_numeric($id))
			throw new Exception('Invalid parameter passed id: '. var_export($id, true));

		$db = DB::factory(DB_APP);

		$q = new Query("SELECT * FROM locales WHERE id = $$0 AND status = ". self::OBJ_STATUS_ACTIVE);
		$row = $db->selectOne($q->addArgument($id));

		if (!$row)
			return null;

		$this->id = $id;
		$this->data = $row;
	}

	public function getByAccountId($account_id) {
		if (!$account_id || !is_numeric($account_id))
			throw new Exception('Invalid parameter passed account_id: '. var_export($account_id, true));

		$db = DB::factory(DB_APP);

		$q = new Query('SELECT * FROM locales WHERE account_id = $$0');
		$res = $db->query($q->addArgument($account_id));

		if (!$res)
			return null;

		while ($row = $res->nextAssoc()) {
			$obj = new Locale();
			$obj->id = (int)$row['id'];
			$obj->data = $row;
			$return []= $obj;
		}

		return $return;
	}

	protected function deleteImpl() {
		die('delete '. $this->id);
	}

	/**
	 * TODO this method is gross, consider something more abstract and moving
	 * validation forms inside these classes
	 */
	public function save($data) {
		$db = DB::factory(DB_APP);

		if ($data['id']) {
			$q = new Query('UPDATE locales SET
				name = $$1,
				description = $$2,
				updated_ts = NOW()
				WHERE id = $$0');
		}
		else {
			$q = new Query('INSERT INTO locales SET
				name = $$1,
				description = $$2,
				account_id = $$3');
		}

		$res = $db->query($q->addArgument($data['id'], $data['name'], $data['description'], isset($data['account_id']) ? $data['account_id'] : null));

		return $db->insertID();
	}
}

?>
