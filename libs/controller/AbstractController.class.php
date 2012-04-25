<?php

abstract class AbstractController {

	protected $data_container;
	protected $request;
    protected $view;

	/**
	 * @param $request Request object of the current request
	 */
	public function __construct(Request $request) {
		$this->request = $request;
		$this->data_container = new DataContainer();
	}

	abstract public function authorize();

    public function render() {
        echo self::viewInstance()->render($this->getTemplate(), $this->data_container->getVars());
    }

	/**
	 * Factory method to return the appropriate controller class to execute the
	 * current request
	 *
	 * @param $request Request object of the current request
	 *
	 * @throws Exception if class cannot be found
	 *
	 * @returns AbstractController subclass on success
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
	 *	 header to be a refresh header, instead of a location one
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


	public function setTemplate($template, $controller = true) {
		return self::viewInstance()->setTemplate(
			$template,
			$controller ? $this->request->getController() : ''
		);
	}

	/**
	 * @TODO rethink this a bit, coupling with view classes, and local data
	 *
	 * get the current page's template
	 *
	 * @returns string - relative path to template directory
	 */
	public function getTemplate() {
		$template = $this->data_container->get('template');

		if (!$template)
			$template = $this->request->getController() .'/'. $this->request->getAction();

		return $template;
	}

	/**
	 * Construct the view object on first use, so that a user could modify the 
	 * defaut view.class config setting before it is created
	 *
	 * @TODO consider adding a parameter to manually override view.class
	 *
	 * @return View
	 */
	protected static function viewInstance() {
		static $view = null;

		if ($view === null)
			$view = AbstractView::factory(Config::get('view.class'));

		return $view;
	}
}

?>
