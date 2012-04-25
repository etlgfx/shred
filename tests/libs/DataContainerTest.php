<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'DataContainer.class.php';

class DataContainerTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		$this->dc = new DataContainer();
	}

	public function testInvalidKey() {
		$this->assertNull($this->dc->get(null));
		$this->assertNull($this->dc->get('does not exist'));
	}

	public function testBadKey() {
		$this->dc->set('one', 'val');
		$this->assertNull($this->dc->get('one.nokey'));
	}

	public function testAddingData() {
		$this->assertEquals(array(), $this->dc->getVars());

		$this->dc->set('key', 'value');
		$this->assertEquals($this->dc->get('key'), 'value');

		$this->dc->append('key', 'value2');
		$this->dc->append('key', 'value3');
		$this->assertEquals($this->dc->get('key'), array('value', 'value2', 'value3'));

		$this->assertEquals($this->dc->get('key.0'), 'value');
		$this->assertEquals($this->dc->get('key.1'), 'value2');

		$data = array('data' => array('nested' => array('values' => 'arefun')));

		$this->dc->set($data);

		$this->assertEquals($this->dc->get(array('data', 'nested', 'values')), 'arefun');
		$this->assertEquals($this->dc->get('data.nested.values'), 'arefun');

		$this->assertTrue($this->dc->is_set('data'));
		$this->assertFalse($this->dc->is_set('no data'));
	}
}

?>
