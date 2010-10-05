<?php

require_once PATH_DB .'DB.class.php';

final class DBmysqli extends DB {
	private $db;
	private $current_db;
	private $server, $port, $username, $password;

	/**
	 * @param $connection array of connection parameters
	 * @param $default_db string default database name
	 */
	public function __construct(array $connection, $default_db = null) {
		$this->server   = isset($connection['host']) ? $connection['host'] : null;
		$this->port     = isset($connection['port']) ? $connection['port'] : null;
		$this->username = isset($connection['username']) ? $connection['username'] : null;
		$this->password = isset($connection['password']) ? $connection['password'] : null;
		$this->socket   = isset($connection['socket']) ? $connection['socket'] : null;

		$this->default_db = $default_db;
		$this->current_db = null;

		$this->db = null;
	}

	/**
	 * connect to the DB specified in construction by the db factory
	 *
	 * @return boolean
	 */
	public function connect() {
		if (!$this->db)
			$this->db = new mysqli($this->server, $this->username, $this->password, $this->default_db, $this->port ? $this->port : 3306, $this->socket);

		if ($this->db instanceof MySQLi) {
			if ($this->db->connect_error)
				throw new Exception("MySQLi Connection Error (". $this->db->connect_errno ."): ". $this->db->connect_error);

			$this->current_db = $this->default_db;
		}

		return $this->db && true;
	}

	/**
	 * select database
	 *
	 * @param $db_name string db name
	 *
	 * @return boolean
	 */
	public function selectDB($dbname) {
		if (!$this->db && !$this->connect())
			return false;

		if ($dbname !== $this->current_db) {
			if ($this->db->select_db($dbname)) {
				$this->current_db = $dbname;
				return true;
			}
			else {
				return false;
			}
		}
		else
			return true;
	}

	/**
	 * close the DB connection
	 */
	public function close() {
		if (!$this->db)
			return;

		$this->db->close();
		$this->db = null;
	}

	/**
	 * Execute the query
	 *
	 * @param $q Query
	 *
	 * @returns DBResult or false
	 */
	public function query(Query $q) {
		$res = false;

		if (!$this->db)
			$this->connect();

		$query = $q->getQuery($this);
		$res = $this->db->query($query);

		if (is_bool($res))
			return $res;
		else if (is_object($res))
			return new DBResultMysqli($res);
		else
			return false;
	}

	/**
	 * @returns int
	 */
	public function affectedRows() {
		if (!$this->db)
			return false;

		return $this->db->affected_rows;
	}

	/**
	 * @returns int
	 */
	public function insertID() {
		if (!$this->db)
			return false;

		return $this->db->insert_id;
	}

	/**
	 * @param string string to escape
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
}

final class DBResultMysqli implements DBResult {
	private $res;

	public function __construct(MySQLi_Result $res) {
		if (!$res)
			throw new Exception('');

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
