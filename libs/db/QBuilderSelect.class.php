<?php

class QBuilderSelect extends QBuilder {

	protected function compile(&$sql, &$params) {
		$params = array();
		$sql = 'SELECT ';

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
