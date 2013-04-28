<?php

namespace Shred;

class DB_QBuilder_Update extends DB_QBuilder_Abstract {

	protected $_values = array();

	public function map(array $data) {
		$this->_values = $data;

		return $this;
	}

	protected function compile(&$sql, &$params) {
		$params = array();
		$sql = 'UPDATE ';

		if (!$this->_table || !$this->_values)
			throw new \RuntimeException('No table to select from or on data to update, you must call ->table() and ->map()');

		$sql .= "`{$this->_table}` ";

		$this->compileValues($sql, $params);
		$this->compileWhere($sql, $params);
		$this->compileLimit($sql, $params);
	}

	protected function compileValues(&$sql, &$params) {
		$p = count($params);

		if ($this->_where) {
			$sql .= 'SET ';

			$val = array();
			foreach ($this->_values as $k => $v) {
				$val []= "`{$k}` =  :$p ";
				$params []= $v;
				$p++;
			}

			$sql .= implode(', ', $val);
		}
	}
}
