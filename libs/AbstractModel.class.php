<?php

abstract class AbstractModel {

	protected static $_validator;
	protected static $_table;
	protected static $_fields;
	protected static $_has;
	protected static $_pk = 'id';

	protected $_data, $_relations;

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
		$qb = QBuilder::select()->from(static::$_table);

		if (is_array(static::$_pk) && is_array($pk)) {
			foreach (static::$_pk as $k) {
				if (!isset($pk[$k]))
					throw new InvalidArgumentException('');

				$qb->where($k, $pk[$k]);
			}
		}
		else if (is_string(static::$_pk)) {
			$qb->where(static::$_pk, $pk);
		}

		$stmt = $qb->limit(1)->execute(PDOFactory::factory('main'));

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ? new static($row) : null;
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

	public function loadRelated($relation) {
		if (!isset(static::$_has[$relation]))
			throw new InvalidArgumentException('Undefined relationship');

		$name = $relation;
		$relation = static::$_has[$relation];

		if (isset($relation['through'])) {
			$stmt = QBuilder::select()
				->from($relation['foreign_table'])
				->join($relation['through']['table'])
				->on($relation['through']['table'] .'.'. $relation['through']['far'], $relation['foreign_table'] .'.'. $relation['foreign_key'])
				->where($relation['through']['near'], $this->{static::$_pk})
				->execute(PDOFactory::factory('main'));
		}
		else {
			$stmt = QBuilder::select()
				->from($relation['foreign_table'])
				->where($relation['foreign_key'], $this->{static::$_pk})
				->execute(PDOFactory::factory('main'));
		}

		$this->_relations[$name] = array();

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$this->_relations[$name] []= $row;
		}
	}

	public function asArray($relations = true) {
		$return = array();

		foreach ($this->_data as $k => $v)
			$return[$k] = $v;

		if ($relations && $this->_relations)
			foreach ($this->_relations as $k => $v)
				$return[$k] = $v instanceof AbstractModel ? $v->asArray($relations) : $v;

		return $return;
	}

		/*
	public function attach($relation, AbstractModel $obj) {
		PDOFactory::factory('main')->prepare('insert ignore into data_objects_images (object_id, field_type_name, image_id) values (?, ?, ?)')
			->execute(array($this->id, 'image', $att->id));
	}
		 */

		/*
	public function detach($relation, AbstractModel $obj) {
		PDOFactory::factory('main')->prepare('insert ignore into data_objects_images (object_id, field_type_name, image_id) values (?, ?, ?)')
			->execute(array($this->id, 'image', $att->id));
	}
		 */

	/*
	public function countRelated($relation) {

	}
	 */
}
