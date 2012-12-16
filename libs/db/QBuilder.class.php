<?php

class QBuilder {
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
				throw new InvalidArgumentException('Invalid Query type'. $type);
		}
	}

	public static function select() {
		$qb = new QBuilder(self::TYPE_SELECT);
		return call_user_func_array(array($qb, 'columns'), func_get_args());
	}

	public static function update() {
		return new QBuilder(self::TYPE_UPDATE);
	}

	public static function insert() {
		return new QBuilder(self::TYPE_INSERT);
	}

	public static function delete() {
		return new QBuilder(self::TYPE_DELETE);
	}

	public function columns() {
		$cols = func_get_args();

		if (!$cols)
			$this->_columns = null;
		else if (!$this->_columns)
			$this->_columns = $cols;
		else
			$this->_columns = array_merge($this->_columns, $cols);

		return $this;
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
			throw new InvalidArgumentException('Expected 2 or 3 args to where()');
	}

	public function limit($limit = null, $offset = null) {
		if ($offset === null && $limit === null)
			$this->_limit = null;
		else if ($offset === null && is_int($limit))
			$this->_limit = array($limit);
		else if (is_int($limit) && is_int($offset))
			$this->_limit = array($limit, $offset);
		else
			throw new InvalidArgumentException('invalid limit specified');

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
					throw new InvalidArgumentException('trying to build IN clause with a non array argument');
				break;

			default:
				throw new InvalidArgumentException('invalid or unknown operator');
		}

		$this->_where []= array($lhs, $op, $rhs);

		return $this;
	}

	protected function compile(&$sql, &$params) {
		$sql = '';
		$params = array();

		switch ($this->_type) {
			case self::TYPE_SELECT:
				$sql .= 'SELECT ';
				break;

			default:
				throw new Exception('TBD');
		}

		if (!$this->_table)
			throw new RuntimeException('No table to select from, you must call ->from()');

		if (!$this->_columns)
			$sql .= '* ';
		else
			$sql .= '`'. implode('`, `', $this->_columns) .'` ';

		$sql .= "FROM `{$this->_table}` ";

		if ($this->_join) {
			foreach ($this->_join as $join) {
				if (!isset($join[3], $join[4]))
					throw new RuntimeException('undefined join condition');

				$lhs = implode('`.`', explode('.', $join[3]));
				$rhs = implode('`.`', explode('.', $join[4]));

				$sql .= "JOIN `{$join[0]}` ON (`$lhs` = `$rhs`) ";
			}
		}

		$p = 0;

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

		if ($this->_limit) {
			$sql .= "LIMIT {$this->_limit[0]} ";

			if (isset($this->_limit[1]))
				$sql .= "OFFSET {$this->_limit[1]} ";
		}
	}

	public function execute(PDO $db) {
		$sql = $params = null;

		$this->compile($sql, $params);

		$stmt = $db->prepare($sql);

		foreach ($params as $k => $v) {
			$type = PDO::PARAM_STR;

			switch (gettype($v)) {
				case 'integer':
					$type = PDO::PARAM_INT;
					break;

				case 'boolean':
					$type = PDO::PARAM_BOOL;
					break;

				case 'NULL':
					$type = PDO::PARAM_NULL;
					break;
			}

			$stmt->bindValue(':'. $k, $v, $type);
		}

		$stmt->execute();

		return $stmt;
	}
}
