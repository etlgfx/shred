<?php

abstract class AbstractModel {

	protected static $_validator;
	protected static $_table;
	protected static $_fields;
	protected static $_pk = 'id';

	protected $_data;

	/**
	 * Constructor ensures that table name and validator object have been
	 * defined.
	 *
	 * @throws Exception
	 */
	protected function __construct(array $data) {
		/*
		if (!$this->_validator instanceof Validator) {
			throw new BadMethodCallException('No validator object configured');
		}

		$this->_fields = $this->_validator->fields();
		 */
		$this->_data = new stdClass();

		foreach ($data as $k => $v)
			$this->_data->{$k} = $v;
	}

	public function __get($k) {
		if (property_exists($this->_data, $k))
			return $this->_data->{$k};
		else
			throw new RuntimeException('unknown property');
	}

	public function __set($k, $v) {
		if (in_array($k, static::$_fields))
			$this->_data->{$k} = $v;
		else
			throw new RuntimeException('unknown property');
	}

	/**
	 * CRUD Create a new record, using the $data passed in
	 *
	 * @param array $data
	 *
	 * @throws Exception
	 *
	 */
	public static function create(array $data = null) {
		/*
		if (!$this->_validator->validate($data))
			throw new InvalidArgumentException('Invalid data');
		 */

		$fields = array_intersect(array_keys($data), static::$_fields);

		if (!$fields)
			throw new InvalidArgumentException('no fields to insert');

		$str = 'INSERT INTO `'. static::$_table .'` (`'. implode('`, `', $fields) . '`) VALUES (:'. implode(', :', $fields) .')';

		$db = PDOFactory::factory('main');

		$stmt = $db->prepare($str);
		foreach ($data as $col => $val) {
			if (isset(static::$_filters[$col]))
				$val = call_user_func(static::$_filters[$col], $val);

			$stmt->bindValue(':'. $col, $val);
		}

		$stmt->execute();
	}

	/**
	 * Find a single record by primary key
	 */
	public static function find($pk) {
		if (!$pk)
			throw new InvalidArgumentException('');

		$pair = null;

		if (is_array(static::$_pk) && is_array($pk)) {
			$pair = array();

			foreach (static::$_pk as $k) {
				if (!isset($pk[$k]))
					throw new InvalidArgumentException('');

				$pair[$k] = $pk[$k];
			}
		}
		else if (is_string(static::$_pk))
			$pair = array(static::$_pk => $pk);

		if ($pair === null)
			throw new InvalidArgumentException('unable to query, invalid parameters');

		$qstr = 'SELECT * FROM `'. static::$_table .'` WHERE ';

		$parts = array();
		foreach ($pair as $k => $v)
			$parts []= '`'. $k .'` = :'. $k;

		$qstr .= implode(' AND ', $parts) .' LIMIT 1';

		$db = PDOFactory::factory('main');
		$stmt = $db->prepare($qstr);
		foreach ($pair as $k => $v)
			$stmt->bindValue(':'. $k, $v);

		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return new static($row);
	}

	/**
	 * Delete a single record by primary key
	 */
	protected static function del($pk) {
	}

	protected static function validator()
	{
	}

	/**
	 * CRUD Update a record
	 */
	public function update(array $data = null) {
		if ((!$filter && !isset($this->id)) || !$data) {
			return false;
		}

		if (!$this->_validator->validate($data)) {
			throw new RuntimeException('Invalid data');
		}

		$query = array();
		$args = array();
		$index = 0;

		foreach ($data as $key => $value) {
			if (isset($this->_fields[$key])) {
				$query []= $key .' = $$'. $index;
				$args[$index] = $value;

				$index++;
			}
		}

		$db = DB::factory('master');

		$query = 'UPDATE '. $this->_table .' SET '. implode(', ', $query);

		if ($filter) {
			echo 'return null';
			return;
			//TODO not impl
			$db->query(new Query($query . $filter->toSql()));
		}
		else {
			$index++;

			$args[$index] = $this->id;
			$db->query(new Query($query .' WHERE id = $$'. $index, $args));
		}

		$this->setFromArray($data);

		return true;
	}

	public function delete() {
	}


	public function copyProperties(AbstractModel $rhs) {
		if (!$rhs instanceof $this)
			throw new Exception('Incompatible objects');

		foreach ($rhs->_data as $k => $v)
			$this->_data->{$k} = $v;

		return $this;
	}
}

?>
