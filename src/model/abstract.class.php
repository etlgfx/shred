<?php

namespace Shred;

abstract class Model_Abstract {

	const REL_HAS = 0x01;
	const REL_BELONG = 0x02;

	protected static $_validator;
	protected static $_table;
	protected static $_fields;
	protected static $_has;
	protected static $_belongs_to;
	protected static $_pk = 'id';

	protected $_data, $_relations, $_dirty, $_queryBuilder; /*, $_loaded*/

	/**
	 * Constructor ensures that table name and validator object have been
	 * defined.
	 *
	 * @param int|mixed $id
	 *
	 * @throws Exception
	 */
	public function __construct($id = null) {
		if ($id) {
			$this->read($id);
		}
		/*
		if (!$this->_validator instanceof Validator) {
			throw new BadMethodCallException('No validator object configured');
		}

		$this->_fields = $this->_validator->fields();
		 */

	}

	/**
	 * TODO clear properly first
	 * TODO set new pk property??
	 */
	public function load(array $data) {
		$this->clear();

		foreach ($data as $k => $v) {
			$this->_data->{$k} = $v;
		}

		return $this;
	}

	/**
	 */
	public function __get($k) {
		if (in_array($k, static::$_fields))
			return property_exists($this->_data, $k) ? $this->_data->{$k} : null;
		else
			throw new \RuntimeException('unknown property: '. var_export($k, true));
	}

	public function __set($k, $v) {
		if (in_array($k, static::$_fields)) {
			if ($this->_dirty)
				$this->_dirty[$k] = true;
			else
				$this->_dirty = array($k => true);

			$this->_data->{$k} = $v;
		}
		else
			throw new \RuntimeException('unknown property: '. $k);
	}

	/**
	 * fancy way to make short hands for using QBuilder
	 *
	 * @param string $name method you wish to call: where, order, limit ...
	 * @param array $arguments
	 *
	 * @return mixed usually $this
	 *
	 * @throw BadMethodCallException
	 */
	public function __call($name, $arguments) {
		switch ($name) {
			case 'where': case 'order': case 'limit':
				call_user_func_array(array($this->queryBuilder(), $name), $arguments);

				return $this;
			default:
				throw new \BadMethodCallException('method does not exist');
		}
	}

	/**
	 * TODO naming, reset?
	 */
	public function clear() {
		$this->_data = new \stdClass();
		$this->_queryBuilder = null;
		$this->_dirty = null;
		$this->_relations = null;
	}

	/**
	 * CRUD Create a new record, using the $data passed in
	 *
	 * @param array $data
	 *
	 * @throws Exception
	 *
	 */
	public function create(array $data = null) {
		/*
		if (!$this->_validator->validate($data))
			throw new InvalidArgumentException('Invalid data');
		 */

		$fields = array_intersect(array_keys($data), static::$_fields);

		if (!$fields)
			throw new \InvalidArgumentException('no fields to insert');

		$str = 'INSERT INTO `'. static::$_table .'` (`'. implode('`, `', $fields) . '`) VALUES (:'. implode(', :', $fields) .')';

		$db = PDOFactory::factory('main');

		$stmt = $db->prepare($str);

		foreach ($fields as $col) {
			$val = $data[$col];

			if (isset(static::$_filters, static::$_filters[$col]))
				$val = call_user_func(static::$_filters[$col], $val);

			$stmt->bindValue(':'. $col, $val);
		}

		$stmt->execute();

		//TODO multiple key
		$data[static::$_pk] = $db->lastInsertId();

		return new static($data);
	}

	/**
	 * read a single record by primary key
	 */
	public function read($pk) {
		if (is_array(static::$_pk) && is_array($pk)) {
			array_map(function ($a) {
				if (!isset($pk[$k])) {
				}
			}, static::$_pk);

			$where = array_intersect_keys(array_flip(static::$_pk), $pk);

			if (count($where) != count($pk)) {
				throw new \InvalidArgumentException('given key does not match configured pk');
			}

			$this->where($pk);
		}
		else if (is_string(static::$_pk)) { //TODO && !is_array $pk
			$this->where(static::$_pk, $pk);
		}

		return $this->findOne();
	}

	/**
	 * Delete a single record by primary key
	protected function del($pk) {
	}

	protected function validator()
	{
	}
	 */

	public function findOne() {
		$stmt = $this->queryBuilder()->limit(1)->execute();

		return $this->load($stmt->fetch(\PDO::FETCH_ASSOC));
	}

	public function findAll() {
		$stmt = $this->queryBuilder()->execute();

		return new Model_Collection($stmt, get_class($this));
	}

	/**
	 * CRUD Update a record
	 */
	public function update(array $data = null) {
		if ($data) {
			unset($data[static::$_pk]);

			foreach ($data as $k => $v)
				$this->{$k} = $v;

			$this->save();
		}

		return $this;
	}

	public function save() {
		//TODO run validation

		if (!$this->{static::$_pk}) //TODO should this be merged with create?
			throw new \RuntimeException('trying to update a non-existing row');

		$map = array();
		foreach ($this->_dirty as $k => $v)
			$map[$k] = $this->{$k};

		Q::update()->table(static::$_table)
			->map($map)
			->where(static::$_pk, $this->{static::$_pk})
			->limit(1)
			->execute(PDOFactory::factory('main'));

		$this->_dirty = null;

		return $this;
	}

	public function delete() {
		if (!$this->{static::$_pk}) //TODO should this be merged with create?
			throw new \RuntimeException('trying to delete a non-existing row');

		Q::delete()->table(static::$_table)
			->where(static::$_pk, $this->{static::$_pk})
			->limit(1)
			->execute(PDOFactory::factory('main')); //TODO cascading delete???

		$this->_data = $this->_dirty = $this->_relations = null;

		return null;
	}

	public function copyProperties(Model_Abstract $rhs) {
		if (!$rhs instanceof $this)
			throw new \Exception('Incompatible objects');

		foreach ($rhs->_data as $k => $v)
			$this->_data->{$k} = $v;

		return $this;
	}

	public function loadRelated($relation) {
		if (!isset(static::$_has[$relation]) && !isset(static::$_belongs_to[$relation]))
			throw new \InvalidArgumentException('Undefined relationship: '. $relation);

		$name = $relation;
		$type = isset(static::$_has[$relation]) ? self::REL_HAS : self::REL_BELONG;
		switch ($type) {
			case self::REL_HAS:
				$relation = static::$_has[$relation];
				break;
			case self::REL_BELONG:
				$relation = static::$_belongs_to[$relation];
				break;
			default:
				throw new \RuntimeException('unknown relationship type: '. $type);
		}

		if (isset($relation['through'])) {
			$stmt = Q::select($relation['foreign_table'] .'.*')
				->from($relation['foreign_table'])
				->join($relation['through']['table'])
				->on($relation['through']['table'] .'.'. $relation['through']['far'], $relation['foreign_table'] .'.'. $relation['foreign_key'])
				->where($relation['through']['near'], $this->{static::$_pk})
				->execute(PDOFactory::factory('main'));
		}
		else if ($type === self::REL_HAS) {
			$stmt = Q::select()
				->from($relation['foreign_table'])
				->where($relation['foreign_key'], $this->{static::$_pk})
				->execute(PDOFactory::factory('main'));
		}
		else if ($type === self::REL_BELONG) {
			$stmt = Q::select()
				->from($relation['foreign_table'])
				->where('id', $this->{$relation['foreign_key']})
				->limit(1)
				->execute(PDOFactory::factory('main'));
		}
		else {
			throw new \RuntimeException('unknown relationship type: '. $type);
		}

		if ($type === self::REL_HAS) {
			$this->_relations[$name] = array();

			$modelconstructor = isset($relation['model']) ? $relation['model'] : null;

			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$this->_relations[$name] []= $modelconstructor ? new $modelconstructor($row) : $row;
			}
		}
		else {
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			$modelconstructor = isset($relation['model']) ? $relation['model'] : null;
			$this->_relations[$name] = $modelconstructor ? new $modelconstructor($row) : $row;
		}

		return $this;
	}

	public function asArray($relations = true) {
		$return = array();

		foreach ($this->_data as $k => $v)
			$return[$k] = $v;

		if ($relations && $this->_relations) {
			foreach ($this->_relations as $k => $v) {
				if (isset(static::$_belongs_to[$k])) {
					$return[$k] = $v instanceof Model_Abstract ? $v->asArray($relations) : $v;
				}
				else if (is_array($v)) {
					$return[$k] = array();

					foreach ($v as $c)
						$return[$k] []= $c instanceof Model_Abstract ? $c->asArray($relations) : $c;
				}
				else {
					$return[$k] = $v instanceof Model_Abstract ? $v->asArray($relations) : $v;
				}
			}
		}

		return $return;
	}

	protected function queryBuilder() {
		if (!$this->_queryBuilder) {
			$this->_queryBuilder = Q::select()->table(static::$_table);
		}

		return $this->_queryBuilder;
	}

		/*
	public function attach($relation, Model_Abstract $obj) {
		PDOFactory::factory('main')->prepare('insert ignore into data_objects_images (object_id, field_type_name, image_id) values (?, ?, ?)')
			->execute(array($this->id, 'image', $att->id));
	}
		 */

		/*
	public function detach($relation, Model_Abstract $obj) {
		PDOFactory::factory('main')->prepare('insert ignore into data_objects_images (object_id, field_type_name, image_id) values (?, ?, ?)')
			->execute(array($this->id, 'image', $att->id));
	}
		 */

	/*
	public function countRelated($relation) {

	}
	 */
}
