<?php

require_once PATH_LIBS .'Request.class.php';
require_once PATH_LIBS .'Util.class.php';

abstract class AbstractController {
	const DEFAULT_ACTION = 'index';

	/**
	 * @param $request Request object of the current request
	 */
	public function __construct(Request $request) {
		$this->request = $request;
	}

	abstract public function authorize();
	abstract public function execute();
	abstract public function render();
	abstract public function error($state);

	/**
	 * Factory method to return the appropriate controller class to execute the
	 * current request
	 *
	 * @param $request Request object of the current request
	 *
	 * @throws Exception if class cannot be found
	 *
	 * @returns AbstractServerController subclass on success
	 */
	public static function factory(Request $request) {
		$controller = null;

		$class = Util::toClassName($request->getController()) .'Controller';
		$path = PATH_APP .'controllers/'. $class .'.class.php';

		if (file_exists($path)) {
			require_once $path;

			return new $class($request);
		}
        else {
            throw new Exception('Controller not found: '. $class);
        }
	}

	/**
	 * @returns string
	 */
	protected static function defaultAction() {
		return self::DEFAULT_ACTION;
	}
}

?>
