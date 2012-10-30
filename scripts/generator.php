<?php

$autoload = parse_ini_string(file_get_contents('php://stdin'));

if (!$autoload) {
	die('Error creating autoload section');
}

$ini['autoload'] = $autoload;

foreach ($ini as $section => $kv) {
	echo '['. $section .']'. PHP_EOL;

	foreach ($kv as $k => $v) {
		echo $k .'='. $v . PHP_EOL;
	}

	echo PHP_EOL;
}

?>
