<?php

/**
 * @class DataContainer
 *
 * The DataContainer class is meant as a simple PHP template variable store
 */
class DataContainer {
	private $vars;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->vars = array();
	}

	/**
	 * @see getReference()
	 *
	 * @param $key string path to the requested data variable
	 *
	 * @retuns mixed
	 */
	public function get($key) {
		return $this->getReference($key);
	}

	/**
	 * sets template variables
	 *
	 * @see getReference()
	 *
	 * @param $key string path to the requested data variable
	 * @param $value mixed value of variable to set
	 */
	public function set($key, $value = null) {
		if ($value === null && is_array($key))
			$this->vars = array_merge($this->vars, $key);
		else {
			$obj =& $this->getReference($key, true);
			$obj = $value;
		}
	}

	/**
	 * appends template variable to an array
	 *
	 * @see getReference()
	 *
	 * @param $key string path to the requested data variable
	 * @param $value mixed value of variable to set
	 */
	public function append($key, $value) {
		$obj =& $this->getReference($key, true);
		if (is_array($obj))
			$obj []= $value;
		else
			$obj = array($obj, $value);
	}

	/**
	 * return whether the given key is set or not in the template vars
	 *
	 * @see getReference()
	 *
	 * @param $key string path to the requested data variable
	 *
	 * @returns boolean
	 */
	public function is_set($key) {
		$obj = $this->getReference($key);
		return isset($obj);
	}

	/**
	 * Get all values
	 *
	 * @returns array 
	 */
	public function getVars() {
		return $this->vars;
	}

	/**
	 * template keys are in the form of bla.sub.subsub
	 *
	 * @param $key string
	 *
	 * @returns array
	 */
	private function templateKeys($key) {
		return explode('.', $key);
	}

	/**
	 * Return the requested data object by reference
	 *
	 * @see templateKeys()
	 *
	 * @param $key string path to the requested data variable
	 * @param $create boolean if true the requested key will be created
	 *
	 * @returns mixed the object requested by the key identifier, or null if not found
     *
     * TODO throw exception??
	 */
	private function & getReference($key, $create = false) {
		if (is_string($key))
			$key = $this->templateKeys($key);

		$null = null;

		if (!is_array($key))
			return $null;

		$obj =& $this->vars;
		foreach ($key as $k) {
			if (isset($obj[$k]))
				$obj =& $obj[$k];
			else if ($create) {
				$obj[$k] = array();
				$obj =& $obj[$k];
			}
			else
				return $null;
		}

		return $obj;
	}
}

?>
