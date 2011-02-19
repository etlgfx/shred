<?php

require_once PATH_LIBS .'DataContainer.class.php';
require_once PATH_LIBS .'Request.class.php';
require_once PATH_LIBS .'Util.class.php';

abstract class AbstractController {

	protected $data_container;
    protected $request;

	/**
	 * @param $request Request object of the current request
	 */
	public function __construct(Request $request) {
		$this->request = $request;
	}

	abstract public function authorize();
	abstract public function render();

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
	 * Redirect to the passed URL, if the current request is in AJAX mode the
	 * Location header will TODO ????
	 *
	 * @param URL $url URL object to redirect to
	 * @param int $timeout number of seconds to wait, this will cause the
	 *     header to be a refresh header, instead of a location one
	 */
	public function redirect(URL $url, $timeout = null) {
		if ($this->request->getUrl() == $url) //TODO hacky, consider changing the interface to the current URL?
			return;

		if (is_int($timeout))
			header('Refresh: '. $timeout .'; url='. $url);
		else
			header('Location: '. $url);
	}


	/**
	 * sets template variables
	 *
	 * @param string $key name of template variable to set
	 * @param mixed $value
	 *
	 * @see DataContainer
	 */
	public function set($key, $value = null) {
		$this->data_container->set($key, $value);
	}

	/**
	 * sets template variables
	 *
	 * @param string $key name of variable
	 * @param mixed $value
	 *
	 * @see DataContainer.append()
	 *
	 * @returns void
	 */
	public function append($key, $value) {
		return $this->data_container->append($key, $value);
	}



	/**
	 * return whether the given key is set or not in the template vars
	 *
	 * @param string $key name of variable to check
	 *
	 * @returns boolean
	 */
	public function exists($key) {
		return $this->data_container->is_set($key);
	}

}

?>
