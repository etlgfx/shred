<?php

namespace Shred;

class Autoload {

	protected $base;

	public function __construct($base) {
		$this->base = $base;

		spl_autoload_register(array($this, 'load'), true);
	}

	/**
	 * @param string $class
	 */
	public function load($class) {
		$ns = explode('\\', strtolower($class));
		$path = explode('_', array_pop($ns));

		if (file_exists($p1 = $this->base . '/classes/' . implode(DIRECTORY_SEPARATOR, array_merge($ns, $path)) .'.class.php')) {
			require $p1;
		}
		else if ($ns && file_exists($p2 = $this->base . '/vendors/'. $ns[0] .'/src/'. implode(DIRECTORY_SEPARATOR, array_merge(array_slice($ns, 1), $path)) .'.class.php')) {
			require $p2;
		}
	}
}

