<?php

/*
if (PHP_VERSION < '5.3')
	die('Unable to initialize, your PHP version must be 5.3 or higher. You currently have: '. PHP_VERSION);
	*/

define('PATH_SHRED', __DIR__ .'/');
define('PATH_CODE', dirname(PATH_SHRED) .'/');

define('PATH_LIBS', PATH_SHRED .'libs/');
define('PATH_DB', PATH_LIBS .'db/');
define('PATH_CONFIG', PATH_CODE .'config/');
define('PATH_VENDORS', PATH_CODE .'vendors/');

// init IP Address
if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
	define('CLIENT_IP', $_SERVER['HTTP_CLIENT_IP']);
}
else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
	define('CLIENT_IP', $_SERVER['HTTP_X_FORWARDED_FOR']);
}
else if (isset($_SERVER['REMOTE_ADDR'])) {
	define('CLIENT_IP', $_SERVER['REMOTE_ADDR']);
}
else {
	define('CLIENT_IP', null);
}

//SERVER constants
if (isset($_SERVER['SERVER_NAME'])) {
	define('SERVER_PATH', dirname($_SERVER['PHP_SELF']) == '/' ? '/' : dirname($_SERVER['PHP_SELF']) .'/');

	$prefix = 'http://';
	$port = '';

	if (isset($_SERVER['HTTPS'])) {
		$prefix = 'https://';

		if ($_SERVER['SERVER_PORT'] != 443)
			$port = ':'. $_SERVER['SERVER_PORT'];
	}
	else if ($_SERVER['SERVER_PORT'] != 80)
		$port = ':'. $_SERVER['SERVER_PORT'];

	define('SERVER_URL', $prefix . $_SERVER['HTTP_HOST'] . $port . SERVER_PATH); //TODO SSL https etc, take ports into account for this shit, subdomains too
	define('REQUEST_URI', SERVER_URL . substr($_SERVER['REQUEST_URI'], strlen(SERVER_PATH)));
}
else {
	define('SERVER_PATH', PATH_CODE);
	//define('SERVER_URL', PATH_CODE);
	//define('REQUEST_URI', PATH_CODE); //TODO this won't work for command line,
	//or unit tests
}

?>
