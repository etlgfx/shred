<?php

require_once dirname(__FILE__) .'/../init.inc.php';
require_once PATH_LIBS .'Autoload.class.php';

$autoload = new Autoload();
$autoload->preloadAll();

?>
