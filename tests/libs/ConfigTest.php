<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'Config.class.php';

class ConfigTest extends PHPUnit_Framework_TestCase {

	public function testInstance() {
		$inst = Config::instance();
		$this->assertInstanceOf('Config', $inst);
		$this->assertSame($inst, Config::instance());
	}

	public function testGetSet() {
		$this->assertNull(Config::get('key'));

		$this->assertFalse(Config::is_set('key'));

		Config::set('key', 'value');

		$this->assertTrue(Config::is_set('key'));

		$this->assertEquals(Config::get('key'), 'value');

		Config::append('key', 'value2');

		$this->assertTrue(Config::is_set('key.0'));
		$this->assertTrue(Config::is_set('key.1'));
		$this->assertTrue(Config::is_set(array('key', '0')));

		$this->assertEquals(Config::get('key'), array('value', 'value2'));

		$this->assertEquals(Config::get('key.0'), 'value');
		$this->assertEquals(Config::get('key.1'), 'value2');
	}
}

?>
