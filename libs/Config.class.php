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
	 * @see DataContainer::get()
	 *
	 * @param $key
     *
     * @returns mixed
	 */
	public static function get($key) {
		return self::instance()->data_container->get($key);
	}

	/**
	 * Set config options
	 *
	 * @see DataContainer::set()
	 *
	 * @param $key string path to the requested data variable
	 * @param $value mixed value of variable to set
	 */
	public static function set($key, $value = null) {
		self::instance()->data_container->set($key, $value);
	}

	/**
	 * Append config options
	 *
	 * @see DataContainer::append()
	 *
	 * @param $key string path to the requested data variable
	 * @param $value mixed value of variable to set
	 */
	public static function append($key, $value) {
		self::instance()->data_container->append($key, $value);
	}
}