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
            throw new Exception('Table name not configured');
        }

        if (!$this->_validator instanceof Validator) {
            throw new Exception('No validator object configured');
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
     * generic method for performing an insert query
     *
     * @throws Exception
     *
     * @param $fields array
     * @param $data array
     *
     * @returns int; inserted integer id
     */
    protected function queryInsert($fields, $data) {
    }

	/**
	 * generic method for performing a single row update query
	 *
	 * @throws Exception
	 *
	 * @param $fields array
	 * @param $data array
	 * @param $id int
	 *
	 * @returns bool
	 */
	protected function queryUpdate($fields, $data, $id) {
		$fields = array_flip($fields);

		$query = array();
		$args = array();
        $max_index = 0;

        foreach ($data as $key => $value) {
            if (isset($fields[$key])) {

                $index = $fields[$key];

                $query []= $key .' = $$'. $index;
                $args[$index] = $value;

                if ($index > $max_index) {
                    $max_index = $index;
                }
            }
        }

        if (count($query) == 0) {
            return null;
        }

        $max_index++;
        $args[$max_index] = $id;

        $query = new Query('UPDATE '. $this->_table .' SET '. implode(', ', $query) .' WHERE id = $$'. $max_index);

        return $query->addArgument($args);
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

    /*
    private function arrayToWhere(array $where) {
        $operators = array('!=', '=', '+', '-', '/', '*', '<', '<=', '>', '>=', 'AND', 'OR', 'XOR', '!');

        $where = array('=' => array('col' => 'column_name', 'val'));
        //$where = array('column_name' => 'val');

        //$where = array('AND' => array('=' => array('col' => 'column_name', 'val'), '=' => array('col' => 'column_name2', 'val2')));
        //$where = array('column_name' => 'val', 'column_name2' => 'val2');

        $str = '';

        foreach ($where as $k => $v) {
            if (in_array($k, $operators) && is_array($v)) {
                if (count($v) == 1 && $k == '!') {
                    $str = $k . $this->arrayToWhere($v)
                }
                else if (count($v) == 2) {
                    $str = $this->arrayToWhere($v)
                }
            }
        }
    }
    */

    /**
     */
    public function create(array $data = null) {
        if (!$this->_validator->validate($data)) {
            throw new Exception('Invalid data');
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
            throw new Exception('error '. var_export($db->error(), true));
        }
        else {
            //$this->id = $db->insertId();
            echo 'yea';
        }
    }

    public function read(ModelFilter $filter = null) {
    }

    public function update(ModelFilter $filter = null, array $data = null) {
    }

    public function delete(ModelFilter $filter = null) {
    }
}

?>
