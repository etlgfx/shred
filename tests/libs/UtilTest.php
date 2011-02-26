<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'Util.class.php';

class UtilTest extends PHPUnit_Framework_TestCase {

	public function testEncodePassword() {
		$this->assertFalse(Util::encodePassword(null, ''));

		$validSalt = '12345678';
		$this->assertEquals($validSalt, substr(Util::encodePassword('password', $validSalt), 0, 8));
		$this->assertEquals(strlen(Util::encodePassword('password', $validSalt)), strlen(Util::encodePassword('password')));

		$this->assertNotEquals(Util::encodePassword('password'), Util::encodePassword('password'));
	}

	public function testVerifyPassword() {
		$password = Util::encodePassword('password');

		$this->assertTrue(Util::verifyPassword('password', $password));
		$this->assertFalse(Util::verifyPassword('not_password', $password));
	}

	public function testGenerateHash() {
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, Util::generateHash());

		$hash = array();
		$pass = true;

		for ($i = 0; $i < 1000; $i++) {
			$v = Util::generateHash();

			if (isset($hash[$v])) {
				$pass = false;
				continue;
			}
			else {
				$hash[$v] = true;
			}
		}

		$this->assertTrue($pass);
	}

	public function testToClassName() {
		$this->assertEquals('ClassName', Util::toClassName('class_name'));
		$this->assertEquals('ClassName', Util::toClassName('_class_name_'));
		$this->assertEquals('Classname', Util::toClassName('classname'));
		$this->assertEquals('Classname', Util::toClassName('CLassname'));
	}

	public function testToMethodName() {
		$this->assertEquals('getObject', Util::toMethodName('get_object'));
		$this->assertEquals('_getObject', Util::toMethodName('_get_object'));
		$this->assertEquals('getObjectYea', Util::toMethodName('get_object_yea'));
	}
}

?>
