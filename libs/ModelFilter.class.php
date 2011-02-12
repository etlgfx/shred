<?php

class ModelFilter {

    protected $filter;
    protected $group;
    protected $order;
    protected $limit;

    public function __construct() {
        $this->filter = null;
        $this->group = null;
        $this->order = null;
        $this->limit = null;
    }

    /*
    public function group(Expression $expr) {
    }
    */

    /**
     * Perform simple filters, e.g. column = 1 AND column2 = 2
     *
     * @param string $column
     * @param mixed $value
     * @param string $op
     *
     * @throws Exception
     */
    public function filter($column, $value, $op = '=') {
        switch ($op) {
            case '>': case '<': case '<=': case '>=': case '=': case '!=':

                break;
            default:
                throw new Exception('unsupported operator: '. $op);
        }

        if (!preg_match('/^[a-z0-9_\.]+$/i', $column)) {
            throw new Exception('Column name can only contain alpha numeric characters, underscores and periods');
        }

        if ($this->filter === null) {
            $this->filter = array();
        }

        $this->filter []= array($column, $value, $op);
    }

    /**
     * Simple ordering method, only simple columns are supported
     *
     * @param string $column
     * @param string $direction
     *
     * @throws Exception
     */
    public function order($column, $direction = 'ASC') {
        switch (strtolower($direction)) {
            case 'asc': case 'up': case 1: case true:
                $direction = 'ASC';
            case 'desc': case 'down': case 0: case false:
                $direction = 'DESC';
            default:
                throw new Exception('Unknown direction: '. $direction);
        }

        if (!preg_match('/^[a-z0-9_\.]+$/i', $column)) {
            throw new Exception('Column name can only contain alpha numeric characters, underscores and periods');
        }

        if ($this->order === null) {
            $this->order = array();
        }

        $this->order []= array($column, $direction);
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @throws Exception on invalid parameer
     *
     * @returns ModelFilter current instance with the modification applied
     */
    public function limit($limit = null, $offset = null) {
        if ($limit === null || $limit === 0) {
            $this->limit = null;

            return $this;
        }

        if (!is_numeric($limit) || (int)$limit != $limit) {
            throw new Exception();
        }

        if ($offset && (!is_numeric($offset) || (int)$offset != $offset)) {
            throw new Exception();
        }

        $this->limit = array($limit);

        if ($offset) {
            $this->limit []= $offset;
        }

        return $this;
    }

    /**
     * @returns string (where bla = bla group by bla order by bla limit bla)
     */
    public function toSql() {
        $sql = '';

        if ($this->filter) {
            $sql .= ' WHERE ';

            $filters = array();

            foreach ($this->filter as $filter) {
                if (is_numeric($filter[1])) {
                    $filters []= $filter[0] .' '. $filter[2] .' '. $filter[1];
                }
                else if ($filter[1] === null && $filter[2] == '=') {
                    $filters []= $filter[0] .' IS NULL';
                }
                else if ($filter[1] === null && $filter[2] == '!=') {
                    $filters []= $filter[0] .' IS NOT NULL';
                }
                else {
                    $filters []= $filter[0] .' '. $filter[2] .' "'. addslashes($filter[1]) .'"';;
                }
            }

            $sql .= implode(' AND ', $filters);
        }

        if ($this->order) {
            $sql .= ' ORDER BY ';

            $orders = array();

            foreach ($this->order as $order) {
                $orders []= $order[0] .' '. $order[1];
            }

            $sql .= implode(', ', $orders);
        }

        if ($this->limit) {
            $sql .= ' LIMIT ';

            if (isset($this->limit[1])) {
                $sql .= $this->limit[1] .', '. $this->limit[0];
            }
            else {
                $sql .= $this->limit[0];
            }
        }

        return $sql;
    }

    /**
     * @returns boolean - whether or not the current filter is intended to only
     * return a single result
     */
    public function isSingle() {
        return $this->limit && $this->limit[0] === 1;
    }
}

?>
