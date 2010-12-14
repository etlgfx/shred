<?php

/**
 * @class HelpShell
 *
 * Help shell retrieves a list of available shells
 */
class HelpShell extends Shell {

	/**
	 * The default shell method to perform
	 */
	public function _default() {
		echo "The following shells are available:". PHP_EOL;

		$dirs = array(PATH_LIBS .'shell/', PATH_CODE .'shell/');
		foreach ($dirs as $dir) {
			if (!is_dir($dir))
				continue;

			$d = dir($dir);

			while ($entry = $d->read()) {
				if (preg_match('/^(.*)Shell\.class\.php$/', $entry, $matches)) {
					list($name, $description) = Shell::factory($matches[1])->description();

					printf("	%-16s %-20s %s\n", strtolower($matches[1]), $name, $description);
				}
			}

			$d->close();
		}

		echo "Try running shell [SHELL] help for more information". PHP_EOL;
	}

	/**
	 * The Help shell uses this to display some usage information
	 *
	 * @returns array('name', 'description')
	 */
	public function description() {
		return array(
			'Help Shell',
			'Display some general information on all availale shells',
		);
	}
}

?>
