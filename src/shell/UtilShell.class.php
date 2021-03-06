<?php

class UtilShell extends AbstractShell {

	public function help() {
		echo 'shell util help' . PHP_EOL;
		echo 'shell util password [password]' . PHP_EOL;
	}

	public function _default() {
		$this->help();
	}

	public function password($password = null, $salt = null) {
		if (!$password) {
			throw new Exception('Please provide a valid password');
		}

		echo $password . PHP_EOL;
		echo Util::encodePassword($password, $salt) . PHP_EOL;
	}

	public function slug($string = null, $length = null) {
		echo $string . PHP_EOL;
		echo '	'. Util::toSlug($string, $length) . PHP_EOL;
	}

	public function description() {
		return array(
			'Util Shell',
			'A bunch of convenience functions',
		);
	}
}

?>
