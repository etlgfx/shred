<?php

class Autoload {
	protected $classes;

	public function __construct() {
		$this->classes = parse_ini_file(PATH_SHRED .'autoload.ini');

		spl_autoload_register(array($this, 'load'), true);
	}

	public function load($class) {
		if (isset($this->classes[$class])) {
			require PATH_LIBS . $this->classes[$class];
		}
	}
}

?>
