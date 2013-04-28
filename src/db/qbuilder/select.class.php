<?php

namespace Shred;

class DB_QBuilder_Select extends DB_QBuilder_Abstract {

	protected function compile(&$sql, &$params) {
		$params = array();
		$sql = 'SELECT ';

		if (!$this->_table)
			throw new \RuntimeException('No table to select from, you must call ->from()');

		if (!$this->_columns)
			$sql .= '* ';
		else {
			$columns = array();

			foreach ($this->_columns as $column) {
				if (strpos($column, '.')) {
					list ($tbl, $col) = explode('.', $column, 2);

					if ($col == '*')
						$columns []= '`'. $tbl .'`.*';
					else
						$columns []= '`'. $tbl .'`.`'. $col .'`';
				}
				else {
					$columns []= '`'. $column .'`';
				}
			}

			$sql .= implode(', ', $columns) .' ';
		}

		$sql .= "FROM `{$this->_table}` ";

		if ($this->_join) {
			foreach ($this->_join as $join) {
				if (!isset($join[3], $join[4]))
					throw new \RuntimeException('undefined join condition');

				$lhs = implode('`.`', explode('.', $join[3]));
				$rhs = implode('`.`', explode('.', $join[4]));

				$sql .= "JOIN `{$join[0]}` ON (`$lhs` = `$rhs`) ";
			}
		}

		$this->compileWhere($sql, $params);

		if ($this->_order) {
			$sql .= 'ORDER BY ';

			$ord = array();
			foreach ($this->_order as $order)
				$ord []= "`{$order[0]}` {$order[1]} ";

			$sql .= implode(', ', $ord);
		}

		$this->compileLimit($sql, $params);
	}
}
