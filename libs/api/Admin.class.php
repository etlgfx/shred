<?php

require_once PATH_DB .'DB.class.php';
require_once PATH_API .'Util.class.php';

class Admin {
	private $valid_user;
	private $data;

	/**
	 * initialize database handle and local properties
	 */
	public function __construct() {
		$this->valid_user = false;
		$this->data = null;
	}

	/**
	 * get parent account id
	 *
	 * @returns int or null, null means no parent account is associated
	 */
	public function getAccountId() {
		return $this->data['account_id'];
	}

	/**
	 * authenticate given login parameters
	 *
	 * @throws Exception on error
	 *
	 * @param $email string
	 * @param $password string
	 *
	 * @returns boolean true on success
	 */
	public function authenticate($email, $password) {
		$db = DB::factory(DB_APP);

		$q = new Query('SELECT *
			FROM admin
			WHERE email = $$0
			LIMIT 1');

		$row = $db->selectOne($q->addArgument($email));

		if (!$row)
			throw new Exception('No results returned');

		if (!Util::verifyPassword($password, $row['password'])) {
			$this->data = null;
			$this->valid_user = false;
		}
		else {
			$this->data = $row;
			$this->valid_user = true;
		}

		return $this->valid_user;
	}

	/**
	 * authenticate given session login parameters
	 *
	 * @throws Exception on error
	 *
	 * @param $id string
	 * @param $hash string
	 *
	 * @returns boolean true on success
	 */
	public function authenticateSession($id, $hash) {
		if (!is_numeric($id))
			throw new Exception('Invalid id given: '. $id);

		$db = DB::factory(DB_APP);

		$q = new Query('SELECT *
			FROM admin
			WHERE id = $$0
			LIMIT 1');

		$row = $db->selectOne($q->addArgument($id));

		if (!$row)
			throw new Exception('No results returned');

		if (!Util::verifyPassword($row['id'] . $row['email'] . $row['password'], $hash)) {
			$this->data = null;
			$this->valid_user = false;
		}
		else {
			$this->data = $row;
			$this->valid_user = true;
		}

		return $this->valid_user;
	}

	/**
	 * Return a flag indicating whether the user is logged in or not
	 *
	 * @returns boolean
	 */
	public function isValidUser() {
		return $this->valid_user;
	}

	/**
	 * Create a new admin user record or update an existing one with a new
	 * password
	 *
	 * @param $username string email address of the desired user
	 * @param $password string password of the desired user
	 *
	 * @throws Exception on error
	 *
	 * @returns boolean true on success
	 */
	public function edit($username, $password) {
		if (!preg_match(RE_EMAIL, $username) || $password == '')
			throw  new Exception('Invalid parameters');

		$db = DB::factory(DB_APP);

		$q = new Query ('SELECT *
			FROM admin
			WHERE email = $$0
			LIMIT 1');

		$row = $db->selectOne($q->addArgument($username));

		$password = Util::encodePassword($password);

		if ($row) {
			$q = new Query('UPDATE admin
				SET password = $$0
				WHERE id = $$1');

			$db->query($q->addArgument($password, $row['id']));
		}
		else {
			$q = new Query('INSERT INTO admin
				SET email = $$0,
					password = $$1');

			$db->query($q->addArgument($username, $password));
		}

		return true;
	}

	/**
	 * @returns int id of associated record
	 */
	public function id() {
		return isset($this->data['id']) ? (int)$this->data['id'] : null;
	}

	/**
	 * @returns mixed
	 */
	public function get($key) {
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * Generate a hash string to use as session authentication
	 *
	 * @param $id int
	 * @param $email string
	 * @param $password string
	 *
	 * @returns string password
	 */
	public function sessionHash($id = null, $email = null, $password = null) {
		if ($id === null && $email === null && $password === null) {
			if (isset($this->data['id']) && isset($this->data['email']) && isset($this->data['password'])) {
				$id = $this->data['id'];
				$email = $this->data['email'];
				$password = $this->data['password'];
			}
			else
				throw new Exception('Bad arguments passed: '. var_export(func_get_args(), true));
		}

		return Util::encodePassword($id . $email . $password);
	}
}

?>
