<?php

require_once PATH_LIBS .'URL.class.php';

abstract class AbstractController {
	const DEFAULT_ACTION = 'index';
	private $url;

	/**
	 * @param $url URL object of the current request
	 */
	public function __construct(URL $url) {
		$this->url = $url;
	}

	abstract public function authorize();
	abstract public function execute();
	abstract public function render();
	abstract public function error($state);

	/**
	 * Factory method to return the appropriate controller class to execute the
	 * current request
	 *
	 * @param $url URL object of the current request
	 *
	 * @throws Exception if class cannot be found
	 *
	 * @returns AbstractServerController subclass on success
	 */
	public static function factory(URL $url) {
		static $registry = array();

		$abstract_controller_class = 'Abstract'. ucfirst(APP_NAME) .'Controller';

		if (!class_exists($abstract_controller_class)) {

			$path = PATH_APP . $abstract_controller_class .'.class.php';

			if (file_exists($path))
				require_once $path;
			else
				throw new Exception("Product Controller not found: $path");
		}

		if (!$url->getAction())
			$url->setAction(call_user_func(array($abstract_controller_class, 'defaultAction')));

		$action = $url->getAction();

		if (!isset($registry[$action])) {

			$c = self::getController($url);
			
			if (!$c && method_exists($abstract_controller_class, 'routing')) {
				$custom_action = call_user_func(array($abstract_controller_class, 'routing'), $url);
				$c = self::getController($url, $custom_action);
			}

			if ($c)
				$registry[$action] = $c;
			else
				throw new Exception("Requested controller doesn't exist: $class - $path");
		}

		return $registry[$action];
	}

	/**
	 * @returns string
	 */
	protected static function defaultAction() {
		return self::DEFAULT_ACTION;
	}

	/**
	 * get a concrete controller class based on the URL or action. URL
	 * parameter is passed on to the new controller class
	 *
	 * @param $url URL object of the current request
	 * @param $custom_action string or null, if null use the action set inside
	 * 	the URL to determine what controller to load
	 * 
	 * @returns AbstractController subclass
	 */
	private static function getController(URL $url, $custom_action = null) {
		$controller = null;

		$class = ucfirst($custom_action ? $custom_action : $url->getAction()) .'Controller';
		$path = PATH_APP .'controllers/'. $class .'.class.php';

		if (file_exists($path)) {
			require_once $path;

			$controller = new $class($url);
		}

		return $controller;
	}

}

?>
