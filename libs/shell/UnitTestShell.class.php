<?php

/**
 * @class UnitTestShell
 *
 * Runs phpunit
 */
class UnitTestShell extends Shell {

	public function _default() {
		$command = "phpunit --colors --verbose ". PATH_SHRED .'tests/';

		$t = $this->params->getOption('t');
		if ($t) {
			$command .= " ". $t;
		}

		passthru($command);
	}

	/**
	 * Display shell usage information
	 *
	 * @returns array('name', 'description')
	 */
	public function description() {
		return array(
			'UnitTest Shell',
			'Run unit tests in the tests directory'
		);
	}
}

?>
