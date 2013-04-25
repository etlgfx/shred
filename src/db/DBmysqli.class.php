<?php

final class DBmysqli extends DB {
	private $db;
	private $server;
	private $port = 3306;
	private $socket = null;
	private $username;
	private $password;
	private $current_db;

	/**
	 * @param array $connection array of connection parameters
	 * @param string $default_db default database name
	 */
	public function __construct($server, $username, $password, $database, $port, $socket) {
		$this->server   = $server;
		$this->username = $username;
		$this->password = $password;

		if ($socket) {
			$this->socket = $socket;
		}

		if ($port) {
			$this->port = $port;
		}

		$this->default_db = $database;
		$this->current_db = null;

		$this->db = null;
	}

	/**
	 * connect to the DB specified in construction by the db factory
	 *
	 * @return boolean
	 */
	public function connect() {
		if (!$this->db) {
			$this->db = new mysqli($this->server, $this->username, $this->password, $this->default_db, $this->port, $this->socket);
		}

		if ($this->db instanceof MySQLi) {
			if ($this->db->connect_error) {
				throw new Exception("MySQLi Connection Error (". $this->db->connect_errno ."): ". $this->db->connect_error);
			}

			$this->current_db = $this->default_db;
		}

		return $this->db && true;
	}

	/**
	 * select database
	 *
	 * @param string $db_name db name
	 *
	 * @return boolean
	 */
	public function selectDB($dbname) {
		if (!$this->db && !$this->connect()) {
			return false;
		}

		if ($dbname !== $this->current_db) {
			if ($this->db->select_db($dbname)) {
				$this->current_db = $dbname;
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return true;
		}
	}

	/**
	 * close the DB connection
	 */
	public function close() {
		if (!$this->db) {
			return;
		}

		$this->db->close();
		$this->db = null;
	}

	/**
	 * Execute the query
	 *
	 * @param Query $q Query
	 *
	 * @returns DBResult or false
	 */
	public function query(Query $q) {
		$res = false;

		if (!$this->db) {
			$this->connect();
		}

		$query = $q->getQuery($this);

		$res = $this->db->query($query);

		if (is_bool($res)) {
			return $res;
		}
		else if (is_object($res)) {
			return new DBResultMysqli($res);
		}
		else {
			return false;
		}
	}

	/**
	 * @returns int
	 */
	public function affectedRows() {
		if (!$this->db) {
			return false;
		}

		return $this->db->affected_rows;
	}

	/**
	 * @returns int
	 */
	public function insertId() {
		if (!$this->db) {
			return false;
		}

		return $this->db->insert_id;
	}

	/**
	 * @param string $string string to escape
	 *
	 * @returns string
	 */
	public function escape($string) {
		if ($this->db) {
			return $this->db->real_escape_string($string);
		}
		else {
			return addslashes($string);
		}
	}

	/**
	 * @param string $filename file to execute as multiquery
	 *
	 * @returns bool
	 */
	public function multiQuery($filename) {
		if (!file_exists($filename)) {
			return false;
		}

		if (!$this->db) {
			$this->connect();
		}

		return $this->db->multi_query(file_get_contents($filename));
	}

	public function error() {
		if (!$this->db) {
			return null;
		}

		return array('error_code' => $this->db->errno, 'error' => $this->db->error);
	}
}

final class DBResultMysqli implements DBResult {
	private $res;

	public function __construct(MySQLi_Result $res) {
		if (!$res) {
			//TODO
			throw new Exception('');
		}

		$this->res = $res;
	}

	public function numRows() {
		return $this->res->num_rows;
	}

	public function nextAssoc() {
		return $this->res->fetch_assoc();
	}

	public function numFields() {
		return $this->res->field_count;
	}

	public function free() {
		$this->res->free();
	}

	public function getFields() {
		return $this->res->fetch_fields();
	}
}
