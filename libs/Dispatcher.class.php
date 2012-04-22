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

	private $state = null;
	private $controller = null;
	private $request = null;

	/**
	 * Constructor, main program entry point
	 *
	 * parse URL, get a controller, authorize, execute, render to stdout
	 */
	public function __construct(Router $router = null, AbstractErrorController $fallback = null) {
		try {
			$this->init($router);
			$this->authorize();
			$this->execute();
			$this->render();
		}
		catch (NotFoundException $e) {
			header('HTTP/1.0 404 Not Found');

			$this->getGenericController($fallback)->error(404, $e->getMessage());
		}
		catch (PermissionException $e) {
			header('HTTP/1.0 403 Forbidden');

			$this->getGenericController($fallback)->error(403, $e->getMessage());
		}
		catch (RedirectException $e) {
			$this->getGenericController($fallback)->redirect($e->getUrl());
		}
		catch (Exception $e) {

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

			$this->getGenericController($fallback)->error($status, $e->getMessage());
		}
	}

	/**
	 * initialize request object using the router specified or the default router
	 *
	 * @param Router $router
	 *
	 * @see Router::route()
	 * @see AbstractController::factory()
	 */
	protected function init(Router $router = null) {
		$this->state = self::STATE_INIT;

		if ($router === null) {
			$router = new Router();
		}

		$this->request = $router->route();

		$this->controller = AbstractController::factory($this->request);
	}

	/**
	 * Run the authorization method defined in the controller instance
	 *
	 * @throws PermissionException
	 */
	protected function authorize() {
		$this->state = self::STATE_AUTH;

		if (!$this->controller->authorize()) {
			throw new PermissionException('Error Authenticating');
		}
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

		try {
			$this->controller->render();
		}
		catch (Exception $e) {
			Log::raise($e, Log::APP_ERROR, Log::ERROR_TYPE_CTRL);

			header('content-type: text/plain;');
			echo Log::inst();
		}
	}

	/**
	 * Return a very basic, generic controller object. This can be used to
	 * render the simplest pages, if nothing else is available.
	 */
	protected function getGenericController(AbstractErrorController $fallback = null) {
		if ($fallback) {
			return $fallback;
		}
		else {
			require_once PATH_LIBS .'GenericController.class.php';
			return new GenericController(new Request('get'));
		}
	}
}

?>
