<?php

/** @class Query
 *
 * @brief This class represents an SQL query object. It allows you to easily
 * build queries without worrying about escaping arguments
 *
 * The desired format of a query with arguments is a string such as:
 *
 * 'SELECT * FROM table WHERE col1 = $$0'
 *
 * The value of the argument $$0 will be substituted by an escaped version of
 * whatever value you set by calling addArgument()
 */
class Query {
	private $parts;
	private $args;

	/**
	 * constructor
	 *
	 * @param string $query query string, optionally with variable arguments
	 */
	public function __construct($query) {
		$this->args = array();
		$this->parts = preg_split('/(\$\$\d+)/', $query, -1, PREG_SPLIT_DELIM_CAPTURE); 

		for ($i = 1; $i < count($this->parts); $i += 2) {
			$this->parts[$i] = intval(substr($this->parts[$i], 2));
		}

		$args = func_get_args();

		if (count($args) > 1) {
			array_shift($args);

			call_user_func_array(array($this, 'addArgument'), $args);
		}
	}

	/**
	 * addArgument several different argument formats
	 *   - single simple type parameter (int|string|etc) this parameter will be
	 *	 appended to the existing list of parameters, so it will become param
	 *	 0 on first call, then 1, etc.
	 *   - single associative array of arguments, where only number type array
	 *	 keys are used, e.g. array(0 => arg1, 1 => arg2)
	 *   - multiple arguments, where the position in the argument list
	 *	 determines the position in the argument array
	 *
	 * @returns Query $this so that you can pass this directly into DB->query()
	 *
	 * @throws Exception on invalid parameters
	 */
	public function addArgument() {
		$args = func_get_args();

		$count = count($args);

		switch ($count) {
			case 0:
				throw new Exception('Invalid parameters, you must supply arguments');

			case 1:
				if (!is_array($args[0])) {
					$this->args []= $args[0];
					break;
				}
				else {
					$args = $args[0]; //no break stmt here, continue to default label
				}

			default:
				foreach ($args as $k => $v) {
					if (is_int($k)) {
						$this->args[$k] = $v;
					}
				}
				break;
		}

		return $this;
	}

	/**
	 * getQuery converts the current query into a string, appending parts to
	 * eachother and escaping arguments
	 *
	 * @param DB $db
	 *
	 * @returns string
	 */
	public function getQuery(DB $db = null) {
		$parts = $this->parts;
		$len = count($parts);
		$args = array();

		for ($i = 1; $i < $len; $i += 2) {

			$arg = $parts[$i];

			if (!array_key_exists($arg, $this->args)) {
				return null;
			}
			else {
				if (!isset($args[$arg])) {
					if (is_numeric($this->args[$arg])) {
						$args[$arg] = $this->args[$arg];
					}
					else {
						$args[$arg] = "'". ($db instanceof DB ? $db->escape($this->args[$arg]) : addslashes($this->args[$arg])) ."'";
					}
				}

				$parts[$i] = $args[$arg];
			}
		}

		return implode('', $parts);
	}

	/**
	 * Convert the object to string
	 *
	 * @see getQuery()
	 *
	 * @returns string
	 */
	public function __toString() {
		return $this->getQuery();
	}
}

?>
