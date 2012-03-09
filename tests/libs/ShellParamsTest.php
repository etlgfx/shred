<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'ShellParams.class.php';

class ParamFail1 extends ShellParams {
	public function getOption($option) {
		return array();
	}
}

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

	public function testFlag() {
		global $argv;

		$argv = array('script.php', '--with-flag', '--disable-other', '--flag2=false', '-f', '1', '--flagg=on', '--flaggg=off', '--some-shit');

		$params = new ShellParams();

		$this->assertEquals(true, $params->getOptionBoolean('f'));
		$this->assertEquals(true, $params->getOptionBoolean('flag'));
		$this->assertEquals(true, $params->getOptionBoolean('flagg'));
		$this->assertEquals(true, $params->getOptionBoolean('some-shit'));
		$this->assertEquals(false, $params->getOptionBoolean('flaggg'));
		$this->assertEquals(false, $params->getOptionBoolean('other'));
		$this->assertEquals(false, $params->getOptionBoolean('flag2'));
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testInvalidBoolean() {
		global $argv;

		$argv = array('script.php', '--flag=asdlkfjasdf');

		$params = new ShellParams();
		$params->getOptionBoolean('flag');
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testInvalidOptionType() {
		global $argv;

		$argv = array('script.php');

		$params = new ParamFail1();
		$params->getOption('sdf');
		$params->getOptionBoolean('string');
	}

	public function testRequiredParams() {
		global $argv;

		$argv = array('script.php', '--flag=required');

		$params = new ShellParams(array('flag'));
		$this->assertEquals('required', $params->getOption('flag'));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testRequiredParamsException() {
		global $argv;

		$argv = array('script.php', '--flag=required');

		$params = new ShellParams(array('flag', 'required'));
	}

	public function testSwitches() {
		global $argv;

		$argv = array('script.php', '--switch', '-s');

		$params = new ShellParams(array(), array('switch', 's'));

		$this->assertEquals(true, $params->getOption('switch'));
		$this->assertEquals(true, $params->getOption('s'));
	}
}

?>
