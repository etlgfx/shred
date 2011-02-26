<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'Router.class.php';

define('PATH_APP_TMP', '/dev/null');

class RouterTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		//$this->config
	}

	public function testGetUri() {
		$yea = new Router();

		//var_export($_SERVER);
	}
}

?>
