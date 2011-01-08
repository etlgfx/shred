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

    public function filter($lft, $rgt, $op = '=') {
        //if
    }

    public function order(Expression $expr, $direction = 'ASC') {
    }

    /**
     * @param $limit int
     * @param $offset int
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
