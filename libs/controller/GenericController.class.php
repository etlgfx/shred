<?php

class GenericController extends AbstractController {

	/**
	 * Override the standard authorize method
	 */
	public function authorize() { return false; }

	public function render() { }

	public function error($status = 404, $message = null) {
        $this->redirect(new URL('/login'), 1);

		header('content-type: text/plain');

		printf("%s: %s", $status, $message);
	}

}

?>
