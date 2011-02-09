<?php

class RedirectException extends Exception {
    protected $url;

    public function __construct($url) {
        parent::__construct('');

        $this->url = $url;
    }

    public function getUrl() {
        return $this->url;
    }
}

?>
