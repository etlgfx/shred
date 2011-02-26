<?php

require_once PATH_LIBS .'URL.class.php';

class RedirectException extends Exception {
	protected $url;

	public function __construct(URL $url) {
		parent::__construct('');

		$this->url = $url;
	}

	public function getUrl() {
		return $this->url;
	}
}

?>
