<?php

require_once PATH_LIBS .'Request.class.php';
require_once PATH_LIBS .'Router.class.php';
require_once PATH_LIBS .'AbstractController.class.php';

/** @class Dispatcher
 *
 * This class is the entry point for SHRED
 */
class Dispatcher {

	const STATE_INIT   = 0x01;
	const STATE_AUTH   = 0x02;
	const STATE_EXEC   = 0x04;
	const STATE_RENDER = 0x08;

	/**
	 * Constructor, main program entry point
	 *
	 * parse URL, get a controller, authorize, execute, render to stdout
	 */
	public function __construct() {
        $router = new Router();
        $request = $router->route();

		$state = self::STATE_INIT;
		$controller = null;
		$continue = true;

		try {
			$controller = AbstractController::factory($request);
		}
		catch (Exception $e) {
			Error::raise('Page Not Found: '. $request .'; '. $e->getMessage(), Error::APP_ERROR, Error::ERROR_TYPE_CTRL);
			$continue = false;
		}

		if ($continue) {
			$state = self::STATE_AUTH;

			try {
				if (!$controller->authorize()) {
					Error::raise('Error Authenticating', Error::USER_ERROR, Error::ERROR_TYPE_CTRL);
					$continue = false;
				}
			}
			catch (Exception $e) {
				Error::raise($e, Error::APP_ERROR, Error::ERROR_TYPE_CTRL);
				$continue = false;
			}
		}

		if ($continue) {
			$state = self::STATE_EXEC;

			try {
				$controller->execute();
			}
			catch (Exception $e) {
				//TODO raise a user friendly message as well?
				//Error::raise($e->getMessage(), Error::APP_ERROR, Error::ERROR_TYPE_CTRL);
				Error::raise($e, Error::APP_ERROR, Error::ERROR_TYPE_CTRL);
				$continue = false;
			}
		}

		if ($continue) {
			try {
				$state = self::STATE_RENDER;
				$controller->render();
			}
			catch (Exception $e) {
				Error::raise($e, Error::APP_ERROR, Error::ERROR_TYPE_CTRL);

				header('content-type: text/plain;');
				echo Error::inst();
			}
		}
		else {
			if (!$controller) { //ALL ELSE FAILED grab the generic Error Controller and call the error method on that
				require_once PATH_LIBS .'ErrorAppController.class.php';
				$controller = new ErrorAppController(new Request('get'));
			}

			$controller->error($state);
		}
	}

}

?>
