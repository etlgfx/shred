<?php

class Autoload {
	protected $classes;

	public function __construct() {
		$this->classes = parse_ini_file(PATH_SHRED .'autoload.ini');

		spl_autoload_register(array($this, 'load'), true);
	}

	/**
	 * @param string $class
	 */
	public function load($class) {
		if (isset($this->classes[$class])) {
			require PATH_LIBS . $this->classes[$class];
		}
	}

	/**
	 * do not use this, only used for unit testing coverage accuracy
	 */
	public function preloadAll() {
		foreach ($this->classes as $file) {
			require_once $file;
		}
	}
}

?>
