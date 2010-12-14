<?php

/*
if (PHP_VERSION < '5.3')
	die('Unable to initialize, your PHP version must be 5.3 or higher. You currently have: '. PHP_VERSION);
	*/

define('PATH_SHRED', dirname(__FILE__) .'/');
define('PATH_CODE', dirname(PATH_SHRED) .'/');

define('PATH_LIBS', PATH_SHRED .'libs/');
define('PATH_DB', PATH_LIBS .'db/');
define('PATH_CONFIG', PATH_CODE .'config/');
define('PATH_VENDORS', PATH_CODE .'vendors/');

if (!defined('APP_CONFIG') && !isset($argv)) {
	define('PATH_APP', dirname($_SERVER['SCRIPT_FILENAME']) .'/');
	$dir = dirname($_SERVER['SCRIPT_FILENAME']);
	define('APP_NAME', substr($dir, strrpos($dir, '/') + 1));
	unset($dir);

	define('APP_CONFIG', PATH_APP .'config/'. APP_NAME .'.conf.php');
    define('PATH_APP_TMP', PATH_APP .'tmp/');
}

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

if (isset($_SERVER['SERVER_NAME'])) {
	define('SERVER_PATH', dirname($_SERVER['PHP_SELF']) == '/' ? '/' : dirname($_SERVER['PHP_SELF']) .'/');
	define('SERVER_URL', 'http://'. $_SERVER['HTTP_HOST'] . SERVER_PATH); //TODO SSL https etc, take ports into account for this shit, subdomains too
	define('REQUEST_URI', SERVER_URL . substr($_SERVER['REQUEST_URI'], strlen(SERVER_PATH)));
}
else {
	define('SERVER_PATH', PATH_CODE);
	//define('SERVER_URL', PATH_CODE);
	//define('REQUEST_URI', PATH_CODE); //TODO this won't work for command line
}

require_once PATH_LIBS .'Config.class.php';
require_once PATH_LIBS .'Util.class.php';
require_once PATH_LIBS .'Error.class.php';
require_once PATH_CONFIG .'global.conf.php';

if (defined('APP_CONFIG') && APP_CONFIG) {
    require_once APP_CONFIG;
}

switch (isset($_SERVER['ENV']) ? $_SERVER['ENV'] : null) {
	case 'production':
		require_once PATH_CONFIG .'production.conf.php';
		break;

	default: 
		require_once PATH_CONFIG .'dev.conf.php';
}

?>
