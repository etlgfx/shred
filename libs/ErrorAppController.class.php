<?php

require_once PATH_LIBS .'AbstractAppController.class.php';

class ErrorAppController extends AbstractAppController {

	/**
	 * Override the standard authorize method
	 */
	public function authorize() { return false; }

	/**
	 * Override the standard execute method which attempts to find an
	 * action method to call, instead just render the page
	 */
	public function execute() { }

}

?>
