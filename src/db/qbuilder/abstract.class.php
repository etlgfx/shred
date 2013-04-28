<?php

namespace Shred;

abstract class DB_QBuilder_Abstract {
	const TYPE_SELECT = 0x01;
	const TYPE_UPDATE = 0x02;
	const TYPE_REPLACE = 0x03;
	const TYPE_DELETE = 0x04;

	protected
		$_type = null,
		$_columns = null,
		$_table = null,
		$_where = array(),
		$_join = array(),
		$_order = array(),
		$_limit = null,
		$_group = null,
		$_having = null;

	public function __construct($type) {
		switch ($type) {
			case self::TYPE_SELECT: case self::TYPE_UPDATE: case self::TYPE_REPLACE: case self::TYPE_DELETE:
				$this->_type = $type;
				break;

			default:
				throw new \InvalidArgumentException('Invalid Query type'. $type);
		}
	}

	public static function select() {
		$qb = new DB_QBuilder_Select(self::TYPE_SELECT);
		return call_user_func_array(array($qb, 'columns'), func_get_args());
	}

	public static function update($table = null) {
		$qb = new DB_QBuilder_Update(self::TYPE_UPDATE);

		if ($table)
			$qb->table($table);

		return $qb;
	}

	public static function insert($table = null) {
		$qb = new DB_QBuilder_Insert(self::TYPE_INSERT);

		if ($table)
			$qb->table($table);

		return $qb;
	}

	public static function delete() {
		return new DB_QBuilder_Delete(self::TYPE_DELETE);
	}

	public function columns() {
		$cols = func_get_args();

		if (count($cols) == 1 && is_array($cols[0]))
			$cols = $cols[0];

		if (!$cols)
			$this->_columns = null;
		else if (!$this->_columns)
			$this->_columns = $cols;
		else
			$this->_columns = array_merge($this->_columns, $cols);

		return $this;
	}

	public function table($table = null) {
		return $this->from($table);
	}

	public function from($table = null) {
		$this->_table = $table;

		return $this;
	}

	public function join($table, $alias = null, $type = 'CROSS') {
		$this->_join []= array($table, $alias, $type);

		return $this;
	}

	public function on($lhs, $rhs) {
		$i = key($this->_join);
		$this->_join[$i] []= $lhs;
		$this->_join[$i] []= $rhs;

		return $this;
	}

	public function where() {
		$args = func_get_args();

		if (count($args) == 2)
			return $this->_where3($args[0], '=', $args[1]);
		else if (count($args) == 3)
			return $this->_where3($args[0], $args[1], $args[2]);
		else
			throw new \InvalidArgumentException('Expected 2 or 3 args to where()');
	}

	public function limit($limit = null, $offset = null) {
		if ($offset === null && $limit === null)
			$this->_limit = null;
		else if ($offset === null && is_int($limit))
			$this->_limit = array($limit);
		else if (is_int($limit) && is_int($offset))
			$this->_limit = array($limit, $offset);
		else
			throw new \InvalidArgumentException('invalid limit specified');

		return $this;
	}

	public function order($col, $dir = 'ASC') {
		if (!is_string($col) || !is_string($dir))
			throw new \InvalidArgumentException('invalid column, should be string');

		$dir = strtoupper($dir);

		if ($dir != 'ASC' && $dir != 'DESC')
			throw new \InvalidArgumentException('invalid direction, should be ASC or DESC');

		$this->_order []= array($col, $dir);

		return $this;
	}

	/**
	 * we're naively assuming that lhs is the column and rhs is the value, 
	 * potential security threat
	 */
	protected function _where3($lhs, $op, $rhs) {
		switch ($op) {
			case '<': case '>': case '<=': case '>=':
				break;

			case '=':
				if ($rhs === null)
					$op = 'IS';
				break;

			case '!=':
				if ($rhs === null)
					$op = 'IS NOT';
				break;

			case 'IN': case 'NOT IN':
				if (!is_array($rhs))
					throw new \InvalidArgumentException('trying to build IN clause with a non array argument');
				break;

			default:
				throw new \InvalidArgumentException('invalid or unknown operator');
		}

		$this->_where []= array($lhs, $op, $rhs);

		return $this;
	}

	abstract protected function compile(&$sql, &$params);

	protected function compileWhere(&$sql, &$params) {
		$p = count($params);

		if ($this->_where) {
			$sql .= 'WHERE ';

			$cond = array();
			foreach ($this->_where as $where) {
				$cond []= "`{$where[0]}` {$where[1]} :$p ";
				$params []= $where[2];
				$p++;
			}

			$sql .= implode('AND ', $cond);
		}
	}

	protected function compileLimit(&$sql, &$params) {
		if ($this->_limit) {
			$sql .= "LIMIT {$this->_limit[0]} ";

			if (isset($this->_limit[1]))
				$sql .= "OFFSET {$this->_limit[1]} ";
		}
	}

	public function execute(\PDO $db) {
		$sql = $params = null;

		$this->compile($sql, $params);

		$stmt = $db->prepare($sql);

		foreach ($params as $k => $v) {
			$type = \PDO::PARAM_STR;

			switch (gettype($v)) {
				case 'integer':
					$type = \PDO::PARAM_INT;
					break;

				case 'boolean':
					$type = \PDO::PARAM_BOOL;
					break;

				case 'NULL':
					$type = \PDO::PARAM_NULL;
					break;
			}

			$stmt->bindValue(':'. $k, $v, $type);
		}

		$stmt->execute();

		return $stmt;
	}
}
