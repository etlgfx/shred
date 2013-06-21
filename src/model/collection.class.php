<?php

namespace Shred;

class Model_Collection implements \Iterator {

	protected $stmt = null;
	protected $class = null;

	protected $_data = array();

	public function __construct(\PDOStatement $stmt, $class) {
		if (!is_string($class) || !$class) {
			throw new \InvalidArgumentException('invalid class name');
		}

		$this->stmt = $stmt;
		$this->class = $class;

		foreach ($this->stmt as $row) {
			$inst = new $class();
			$this->_data []= $inst->load($row);
		}
	}

	public function current() {
		return current($this->_data);
	}

	public function key() {
		return key($this->_data);
	}

	public function next() {
		return next($this->_data);
	}

	public function rewind() {
		return reset($this->_data);
	}

	public function valid() {
		return isset($this->_data[$this->key()]);
	}

	public function asArray() {
		$return = array();

		foreach ($this->_data as $obj) {
			$return []= $obj->asArray();
		}

		return $return;
	}
}
