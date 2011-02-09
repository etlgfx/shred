<?php

require_once PATH_LIBS .'Util.class.php';

class UtilShell extends Shell {

    public function help() {
        echo 'shell util help' . PHP_EOL;
        echo 'shell util password [password]' . PHP_EOL;
    }

    public function _default() {
        $this->help();
    }

    public function password($password = null) {
        if (!$password) {
            throw new Exception('Please provide a valid password');
        }

        echo $password . PHP_EOL;
        echo Util::encodePassword($password) . PHP_EOL;
    }

    public function description() {
        return array(
            'Util Shell',
            'A bunch of convenience functions',
        );
    }
}

?>
