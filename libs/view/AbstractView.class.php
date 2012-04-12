<?php

abstract class AbstractView {

	abstract public function render($template, array $data);
	abstract public function exists($template);

	/**
	 * Factory method to return the appropriate View class
	 *
	 * @param $class string
	 *
	 * @throw Exception if class cannot be found
	 *
	 * @returns AbstractView subclass on success
	 */
	public static function factory($class = 'twig') {
		$class = Util::toClassName($class) .'View';
		$path = PATH_LIBS .'view/'. $class .'.class.php';

		if (file_exists($path)) {
			require_once $path;

			return new $class();
		}
		else {
			throw new Exception('View Class `'. $class .'` not found in: '. $path);
		}
	}

}

?>
