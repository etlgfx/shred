<?php

namespace Shred;

class Controller_Generic extends Controller_Abstract implements Controller_IError {

	/**
	 * Override the standard authorize method
	 */
	public function authorize() { return false; }

	public function render() { }

	public function error(\Exception $e, $status = 404, $message = null) {
		header('content-type: text/plain');

		printf("%s: %s", $status, $message);

		Log::raise($e);
	}
}

?>
