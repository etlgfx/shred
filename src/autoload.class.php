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

		if ($ns) {
			if (file_exists($p1 = $this->base . $ns[0] .'/classes/'. implode(DIRECTORY_SEPARATOR, array_merge(array_slice($ns, 1), $path)) .'.class.php')) {
				require $p1;
			}
			else if (file_exists($p2 = $this->base . 'vendors/'. $ns[0] .'/src/'. implode(DIRECTORY_SEPARATOR, array_merge(array_slice($ns, 1), $path)) .'.class.php')) {
				require $p2;
			}
			else {
				echo $class . PHP_EOL;
				echo $p1 . PHP_EOL;
				echo $p2 . PHP_EOL;
				die();
			}
		}
		else if (file_exists($p3 = $this->base . 'classes/' . implode(DIRECTORY_SEPARATOR, $path) .'.class.php')) {
			require $p3;
		}
		else {
			echo $class . PHP_EOL;
			echo $p3 . PHP_EOL;
			die();
		}
	}
}

