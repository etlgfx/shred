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
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, Util::generateHash());

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

	public function testMimeToExtension() {
		$this->assertEquals(Util::mimeToExtension('image/jpeg'), '.jpg');
		$this->assertEquals(Util::mimeToExtension('image/jpg'), '.jpg');

		$this->assertEquals(Util::mimeToExtension('image/png'), '.png');

		$this->assertEquals(Util::mimeToExtension('image/gif'), '.gif');

		$this->assertEquals(Util::mimeToExtension('image/tiff'), '.tiff');

		$this->assertEquals(Util::mimeToExtension('audio/ogg'), '.ogg');

		$this->assertEquals(Util::mimeToExtension('audio/mpeg'), '.mp3');

		$this->assertEquals(Util::mimeToExtension('image/x-icon'), '.ico');

		$this->assertEquals(Util::mimeToExtension('application/pdf'), '.pdf');

		$this->assertNull(Util::mimeToExtension());
	}

	/**
	 * @expectedException Exception
	 */
	public function testInvalidTempFileCall() {
		Util::tempFile('/does/not/exist');
	}

	/**
	 * @expectedException Exception
	 */
	public function testInvalidTempFileCallInvalidPathString() {
		Util::tempFile(2309);
	}

	/**
	 * @expectedException Exception
	 */
	public function testInvalidTempFileCallInvalidPrefix() {
		Util::tempFile(null, 2332);
	}

	/**
	 * @expectedException Exception
	 */
	public function testInvalidTempFileCallInvalidSuffix() {
		Util::tempFile(null, null, array('boo'));
	}

	public function testTempFile() {
		$testNormal = '#^/tmp/[0-9a-f]+$#i';
		$ret = Util::tempFile();

		$this->assertTrue(1 == preg_match($testNormal, $ret));
		$this->assertTrue(is_writable($ret));

		$ret = Util::tempFile('/tmp', 'pre_', '.jpg');
		$this->assertTrue(1 == preg_match('#^/tmp/pre_[0-9a-f]+.jpg$#i', $ret));
		$this->assertTrue(is_writable($ret));

		$ret = Util::tempFile('/tmp', 'pre', '.jpg', true);
        $this->assertTrue(is_writable($ret));
	}
}

?>
