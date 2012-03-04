<?php

require_once 'init.inc.php';
require_once PATH_LIBS .'Shell.class.php';

if (isset($argv[1]) && preg_match('#^[a-z_-]+$#i', $argv[1])) {
	try {
		$shell = Shell::factory($argv[1]);

		$shell->execute();
	}
	catch (Exception $e) {
		echo $e . PHP_EOL;
		exit(1);
	}
}
else {
	echo "Shred Framework Usage:
	shred [shellname]
		- or -
	shred help". PHP_EOL;

	exit(1);
}

?>
