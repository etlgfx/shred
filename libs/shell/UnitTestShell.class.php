<?php

/**
 * @class UnitTestShell
 *
 * Runs phpunit
 */
class UnitTestShell extends AbstractShell {

	public function _default() {
		$command = "phpunit --colors --coverage-html ". PATH_SHRED ."coverage/ --verbose ". PATH_SHRED .'tests/';

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
			'Unit Test Shell',
			'Run unit tests in the tests directory'
		);
	}
}

?>
