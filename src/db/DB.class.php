<?php

/** @class DB
 *
 * Abstract class defining a common interface for database drivers
 */
abstract class DB {

	/**
	 * Factory method for the DB classes
	 *
	 * @param string $db_name name of db handle
	 *
	 * @returns DB implemented subclass
	 */
	public static function & factory($db_name) {
		static $dbs = array();

		$return = null;

		//ensure one db object per db server
		if (isset($dbs[$db_name])) {
			return $dbs[$db_name];
		}

		$dbconfig = Config::get('db.'. $db_name);

		if (isset($dbconfig['driver'])) {
			$driver_class = 'DB'. strtolower($dbconfig['driver']);
			$driver_path = PATH_DB . $driver_class .'.class.php';

			$dbs[$db_name] = $return = new $driver_class(
				isset($dbconfig['server'])   ? $dbconfig['server']   : null,
				isset($dbconfig['username']) ? $dbconfig['username'] : null,
				isset($dbconfig['password']) ? $dbconfig['password'] : null,
				isset($dbconfig['database']) ? $dbconfig['database'] : null,
				isset($dbconfig['port'])     ? $dbconfig['port']     : null,
				isset($dbconfig['socket'])   ? $dbconfig['socket']   : null
			);
		}
		else {
			throw new RuntimeException('Unable to initialize DB object, no config found for db: '. $db_name);
		}

		if (!$return || !$return instanceof DB) {
			throw new Exception('Unable to instantiate DB object for - '. $db_name .': '. var_export($dbconfig, true));
		}

		return $return;
	}

	/**
	 * @param array $connection array of connection parameters
	 * @param string $default_db default database name
	 */
	abstract public function __construct($server, $username, $password, $database, $port, $socket);

	/**
	 * connect to the DB specified in construction by the db factory
	 *
	 * @return boolean
	 */
	abstract public function connect();

	/**
	 * select database
	 *
	 * @param string $db_name db name
	 *
	 * @return boolean
	 */
	abstract public function selectDB($db_name);

	/**
	 * close the DB connection
	 */
	abstract public function close();

	/**
	 * Execute the query
	 *
	 * @param Query $q
	 *
	 * @returns DBResult or false
	 */
	abstract public function query(Query $q);

	/**
	 * Execute the passed query and return the first row as an associative
	 * array
	 *
	 * @param Query $q
	 * @param bool $fancy_array if true, will split query results into arrays using column names separated by dots
	 *
	 * @see deepResult()
	 *
	 * @returns associative array of results or false
	 */
	final public function selectOne(Query $q, $fancy_array = false) {
		$ret = false;

		$res = $this->query($q);

		if ($res instanceof DBResult) {
			$ret = $res->nextAssoc();
			$res->free();
		}

		if ($fancy_array) {
			$ret = $this->deepResult($ret);
		}

		return $ret;
	}

	/**
	 * Execute the passed query and return the entire result set as an array of
	 * associative arrays
	 *
	 * @param Query $q
	 * @param bool $fancy_array if true, will split query results into arrays using column names separated by dots
	 *
	 * @see deepResult()
	 *
	 * @returns array of associative arrays of all rows matched
	 */
	final public function select(Query $q, $fancy_array = false) {
		$ret = false;

		$res = $this->query($q);

		if ($res instanceof DBResult) {
			//$res->getFields();
			$ret = array();
			while ($row = $res->nextAssoc()) {
				if ($fancy_array) {
					$row = $this->deepResult($row);
				}

				$ret []= $row;
			}

			$res->free();
		}

		return $ret;
	}

	/**
	 * @param string $string string to escape
	 *
	 * @returns string
	 */
	abstract public function escape($string);

	/**
	 * @returns int
	 */
	abstract public function affectedRows();

	/**
	 * @returns int
	 */
	abstract public function insertId();

	/**
	 * @param array $row
	 *
	 * @returns array
	 */
	final protected function deepResult($row) {
		$newkeys = array();

		foreach ($row as $k => $v) {
			$table = $column = null;
			list($table, $column) = explode('.', $k, 2);

			if ($column) {
				if (!isset($newkeys[$table])) {
					$newkeys[$table] = array();
				}

				$newkeys[$table][$column] = $v;

				unset($row[$k]);
			}
		}

		return $row + $newkeys;
	}

	/**
	 * @param string $filename file to execute as multiquery
	 *
	 * @returns bool
	 */
	abstract public function multiQuery($filename);

	/**
	 */
	abstract public function error();
}

/** @class DBResult
 *
 * Defines an interface for DBResult objects
 */
interface DBResult {

	/**
	 * number of fields returned
	 *
	 * @returns int
	 */
	public function numFields();

	/**
	 * number of rows returned
	 *
	 * @returns int
	 */
	public function numRows();

	/**
	 * get the next row
	 *
	 * @returns array
	 */
	public function nextAssoc();

	/**
	 * free result resource
	 */
	public function free();
}
