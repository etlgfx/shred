<?php

require_once PATH_LIBS .'DataContainer.class.php';

/**
 * @class Config
 * @brief Config class is a singleton container for configuration options
 *
 * This class uses the DataContainer class internally to store the data itself
 * so most public methods from DataContainer are aliased here for convenience.
 */
class Config {

	private $data_container;

	/**
	 * private constructor initializes the data container instance
	 */
	private function __construct() {
		$this->data_container = new DataContainer();
	}

	/**
	 * Singleton instance method
	 *
	 * @returns Config
	 */
	public static function & instance() {
		static $self = null;
		
		if ($self === null)
			$self = new Config();

		return $self;
	}

	/**
	 * Retrieve config options
	 *
	 * @see DataContainer.get()
	 *
	 * @param string $key
	 *
	 * @returns mixed
	 */
	public static function get($key) {
		return self::instance()->data_container->get($key);
	}

	/**
	 * Set config options
	 *
	 * @see DataContainer.set()
	 *
	 * @param string $key path to the requested data variable
	 * @param mixed $value value of variable to set
	 */
	public static function set($key, $value = null) {
		self::instance()->data_container->set($key, $value);
	}

	/**
	 * Append config options
	 *
	 * @see DataContainer.append()
	 *
	 * @param string $key path to the requested data variable
	 * @param mixed $value value of variable to set
	 */
	public static function append($key, $value) {
		self::instance()->data_container->append($key, $value);
	}

	/**
	 * return whether the given key is set or not in the template vars
	 *
	 * @see DataContainer.is_set()
	 *
	 * @param string $key path to the requested data variable
	 *
	 * @returns boolean
	 */
	public static function is_set($key) {
		return self::instance()->data_container->is_set($key);
	}
}
