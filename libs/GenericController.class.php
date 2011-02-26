<?php

require_once PATH_LIBS .'AbstractAppController.class.php';

class GenericController extends AbstractController {

	/**
	 * Override the standard authorize method
	 */
	public function authorize() { return false; }

	public function render() { }

	public function error($status = 404, $message = null) {
		header('content-type: text/plain');

		printf("%s: %s", $status, $message);
	}

}

?>
