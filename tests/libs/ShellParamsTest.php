<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'ShellParams.class.php';

class ShellParamsTest extends PHPUnit_Framework_TestCase {

	public function testArguments() {
		global $argv;

		$argv = array('script.php', '1', '2');

		$params = new ShellParams();

		$this->assertEquals(array('1', '2'), $params->getArguments());
		$this->assertEquals(array('2'), $params->getArguments(1));
		$this->assertEquals('1', $params->getArgument(0));
		$this->assertEquals('2', $params->getArgument(1));

		$argv = array('script.php', '1', '-a', 'xxx', '2');

		$params = new ShellParams();

		$this->assertEquals(array('1', '2'), $params->getArguments());
	}

	public function testOptions() {
		global $argv;

		$argv = array('script.php', '-o', '1', 'arg');

		$params = new ShellParams();

		$this->assertEquals('1', $params->getOption('o'));
	}
}

?>
