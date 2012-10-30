<?php

/** @class Dispatcher
 *
 * This class is the entry point for SHRED
 */
class Dispatcher {

	const STATE_INIT   = 0x01;
	const STATE_AUTH   = 0x02;
	const STATE_EXEC   = 0x04;
	const STATE_RENDER = 0x08;

	protected $state = null;
	protected $controller = null;
	protected $request = null;
	protected $router = null;
	protected $permissionHandler = null;
	protected $notFoundHandler = null;

	/**
	 * Constructor, main program entry point
	 *
	 * parse URL, get a controller, authorize, execute, render to stdout
	 */
	public function __construct() {
	}

	public function router(Router $router = null) {
		if ($router)
			$this->router = $router;

		return $this->router ? $this->router : new Router();
	}

	public function permissionExceptionHandler(IErrorController $controller = null) {
		if ($controller)
			$this->permissionHandler = $controller;

		return $this->permissionHandler;// ? $this->permissionHandler : ;
	}

	public function notFoundExceptionHandler(IErrorController $controller = null) {
		if ($controller)
			$this->notFoundHandler = $controller;

		return $this->notFoundHandler;
	}

	public function dispatch(IErrorController $errorController = null) {
		try {
			$this->init();
			$this->authorize();
			$this->execute();
			$this->render();
		}
		catch (NotFoundException $e) {
			header('HTTP/1.0 404 Not Found');

			$this->getGenericController($errorController)->error($e, 404, $e->getMessage());
		}
		catch (PermissionException $e) {
			header('HTTP/1.0 403 Forbidden');

			$this->getGenericController($errorController)->error($e, 403, $e->getMessage());
		}
		catch (RedirectException $e) {
			$this->getGenericController($errorController)->redirect($e->getUrl());
		}
		catch (Exception $e) {
			Log::raise($e);

			$status = null;

			switch ($this->state) {

				case self::STATE_INIT:
					header('HTTP/1.0 404 Not Found');
					$status = 404;
					break;

				case self::STATE_AUTH:
					header('HTTP/1.0 403 Forbidden');
					$status = 403;
					break;

				case self::STATE_EXEC:
				case self::STATE_RENDER:
				default:
					header('HTTP/1.0 400 Bad Request');
					$status = 400;
					break;
			}

			$this->getGenericController($errorController)->error($e, $status, $e->getMessage());
		}
	}

	/**
	 * initialize request object using the router specified or the default router
	 *
	 * @see Router::route()
	 * @see AbstractController::factory()
	 */
	protected function init() {
		$this->state = self::STATE_INIT;

		$this->request = $this->router()->route($_SERVER['REQUEST_METHOD'], trim($_SERVER['REQUEST_URI'], '/'));

		$this->controller = AbstractController::factory($this->request);
	}

	/**
	 * Run the authorization method defined in the controller instance
	 *
	 * @throws PermissionException
	 */
	protected function authorize() {
		$this->state = self::STATE_AUTH;
		$this->controller->authorize();
	}

	/**
	 * Determine whether we can call the requested action (controller method).
	 * If so call it. If not (it's not public), throw an exception.
	 *
	 * @throws PermissionException
	 */
	protected function execute() {
		$this->state = self::STATE_EXEC;

		$method = $this->request->getAction();

		$reflector = new ReflectionMethod($this->controller, $method);

		if ($reflector->isPublic()) {
			call_user_func_array(array($this->controller, $method), $this->request->getParams());
		}
		else {
			Log::raise('That\'s not a public method, asshole', Log::APP_ERROR, Log::ERROR_TYPE_CTRL);
			throw new PermissionException('That action is not supported');
		}
	}

	/**
	 * Call the controller render method. If it throws an exception try to
	 * recover by just dumping out the errors.
	 *
	 * TODO consider changing this to throw an exception trigger 404 or similar 
	 */
	protected function render() {
		$this->state = self::STATE_RENDER;

		//try {
			$this->controller->render();
		/*}
		catch (Exception $e) {
			Log::raise($e, Log::APP_ERROR, Log::ERROR_TYPE_CTRL);

			header('content-type: text/plain;');
			echo Log::inst();
		}
		 */
	}

	/**
	 * Return a very basic, generic controller object. This can be used to
	 * render the simplest pages, if nothing else is available.
	 *
	 * @param IErrorController $errorController
	 *
	 * @return IErrorController intsance
	 */
	protected function getGenericController(IErrorController $errorController = null) {
		if ($fallback) {
			return $fallback;
		}
		else if ($this->controller && $this->controller instanceof IErrorController) {
			return $this->controller;
		}
		else {
			return new GenericController(new Request('get'));
		}
	}
}

?>
