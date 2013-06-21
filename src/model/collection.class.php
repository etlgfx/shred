<?php

namespace Shred;

class Model_Collection implements \Iterator, \ArrayAccess, \Countable {

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

	public function offsetExists($offset) {
		return isset($this->_data[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		$this->_data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		if (isset($this->_data[$offset])) {
			unset($this->_data[$offset]);
		}
	}

	public function count() {
		return count($this->_data);
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
