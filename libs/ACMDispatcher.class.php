<?php

require_once PATH_LIBS .'URL.class.php';
require_once PATH_LIBS .'AbstractController.class.php';

/** @class ACMDispatcher
 *
 * This class is the entry point for the Abstract Content Management REST API
 * Server, it can output either XML or JSON, whatever the user prefers
 */
class ACMDispatcher {

	/**
	 * Constructor, main program entry point
	 *
	 * parse URL, get a controller, authorize, execute, render to stdout
	 */
	public function __construct() {
		$url = new URL(REQUEST_URI);

		$controller = AbstractController::factory($url);

		try {
			if (!$controller->authorize())
				Error::raise('Error Authenticating', Error::USER_ERROR, Error::ERROR_TYPE_CTRL);
			else
				$controller->execute();

			try {
				$controller->render();
			}
			catch (Exception $e) {
				Error::raise('Failed to render: '. get_class($e) .'; '. $e, Error::APP_ERROR, Error::ERROR_TYPE_CTRL);

				header('content-type: text/plain;');
				echo Error::inst();
			}
		}
		catch (Exception $e) {
			Error::raise('Failed to execute: '. $e->getMessage(), Error::APP_ERROR, Error::ERROR_TYPE_CTRL);

			$controller->error();
		}
	}

}
