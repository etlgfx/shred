<?php

require_once PATH_LIBS .'Validator.class.php';
require_once PATH_LIBS .'ModelFilter.class.php';
require_once PATH_DB .'DB.class.php';
require_once PATH_DB .'Query.class.php';

abstract class AbstractModel {

	protected $_validator;
	protected $_table;
	protected $_fields;

	/**
	 * Constructor ensures that table name and validator object have been
	 * defined.
	 *
	 * @throws Exception
	 */
	public function __construct() {
		if (!$this->_table) {
			throw new BadMethodCallException('Table name not configured');
		}

		if (!$this->_validator instanceof Validator) {
			throw new BadMethodCallException('No validator object configured');
		}

		$this->_fields = $this->_validator->fields();
	}

	/**
	 * Convert the internal data to arrays
	 *
	 * @returns array
	 */
	public function toArray() {
		$res = array();

		foreach ($this as $k => $v) {
			if ($k[0] == '_') {
				continue;
			}

			if ($v instanceof AbstractModel) {
				$res[$k] = $v->toArray();
			}
			else if (strpos($k, '_ts') && is_string($v)) {
				if ($v == '0000-00-00 00:00:00') {
					$res[$k] = null;
				}
				else {
					$ts = strtotime($v);

					$res[$k] = array(
						'rfc822' => date('r', $ts),
						'timestamp' => $ts,
						'raw' => $v,
					);
				}
			}
			else if (($k == 'id' || strpos($k, '_id')) && is_numeric($v)) {
				$res[$k] = (int)$v;
			}
			else if (is_array($v)) {
				$v = $this->toArray($v);
			}
		}

		return $res;
	}

	/**
	 * Get multiple records.
	 *
	 * @throws Exception
	 *
	 * @param $where array
	 * @param $order array
	 *
	 * @returns array of Model objects
	 */
	public function getAll(array $where = null, array $order = null, $limit = null) {
		$db = DB::factory('master');

		$query = 'SELECT * FROM '. $this->_table;

		$i = 0;

		if ($where) {
			$query .= ' WHERE ';

			$w = array();

			foreach ($where as $key => $value) {
				if (preg_match('/^[a-z0-9_]+$/', $key)) {
					$w []= $key .' = $$'. $i++;
				}
			}

			$query .= implode(' AND ', $w);
		}

		if ($order) {
			$query .= ' ORDER BY ';

			$o = array();
			foreach ($order as $key => $direction) {
				if (is_string($key)) {
					$o []= $key .' '. $direction;
				}
			}

			$query .= implode(', ', $o);
		}

		if ($limit && is_int($limit)) {
			$query .= ' LIMIT '. $limit;
		}

		if ($where) {
			$query = new Query($query, array_values($where));
		}
		else {
			$query = new Query($query);
		}

		$data = $db->select($query);

		$result = array();

		foreach ($data as $row) {
			$class = get_class($this);
			$obj = new $class();

			foreach ($row as $k => $v) {
				//TODO is this valid?
				$obj->$k = $v;
			}

			$result []= array($class => $obj);
		}

		return $result;
	}

	/**
     * CRUD Create a new record, using the $data passed in
     *
     * @param $data
     *
     * @throws Exception
     *
	 */
	public function create(array $data = null) {
		if (!$this->_validator->validate($data)) {
			throw new InvalidArgumentException('Invalid data');
		}

		$str = 'INSERT INTO '. $this->_table .' SET ';

		$items = array();
		$values = array();
		$i = 0;

		foreach ($this->_fields as $key) {
			if (!isset($data[$key])) {
				continue;
			}

			$items[$i] = $key .' = $$'. $i;
			$values[$i++] = $data[$key];
		}

		$query = new Query($str . implode(', ', $items), $values);

		$db = DB::factory('master');

		if (!$db->query($query)) {
			throw new RuntimeException(var_export($db->error(), true));
		}
		else {
            $this->setFromArray($data);

			$this->id = $db->insertId();
		}

		return $this; //TODO should this be this or a boolean?
	}

    /**
     * CRUD Read one or more records
     */
	public function read(ModelFilter $filter = null) {
		if (!$filter) {

			$filter = new ModelFilter();

			if (isset($this->id)) {
				$filter->filter('id', $this->id);
			}
		}

		$db = DB::factory('master');

		if ($filter->isSingle()) {
			$row = $db->selectOne(new Query('SELECT * FROM '. $this->_table . $filter->toSql()));

            if ($row) {
                return $this->setFromArray($row);
            }
            else {
                return null;
            }
		}
		else {
			$res = $db->query(new Query('SELECT * FROM '. $this->_table . $filter->toSql()));

			$ret = array();
			$class = get_class($this);

			while ($row = $res->nextAssoc()) {
				$obj = new $class();
                $ret []= $obj->setFromArray($row);
			}
		}

		return $ret;
	}

    /**
     * CRUD Update a record
     */
	public function update(array $data = null, ModelFilter $filter = null) {
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

    /**
     * CRUD Delete a record
     */
	public function delete(ModelFilter $filter = null) {
		$db = DB::factory('master');

		if (!$filter) {

			if (isset($this->id)) {
				$db->query(new Query('DELETE FROM '. $this->_table .' WHERE id = $$0 LIMIT 1', $this->id));
			}
		}
		else {
			$db->query(new Query('DELETE FROM '. $this->_table .' WHERE '. $filter->toSql()));
		}
	}

    /**
     * Set the key value pairs supplied into the instance's properties. Only 
     * keys starting with alpha characters are accepted, and only keys that are 
     * already known to the instance in the _fields array.
     *
     * @param array $data
     *
     * @returns AbstractModel, current instance
     */
    protected function setFromArray(array $data) {
        foreach ($data as $k => $v) {
            if (!ctype_alpha($k[0])) {
                continue;
            }

            if (isset($this->_fields[$k])) {
                $this->{$k} = $v;
            }
        }

        return $this;
    }
}

?>
