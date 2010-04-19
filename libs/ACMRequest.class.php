<?php

class ACMRequest {
	function __construct() {
	}

	function setAction($action) {
		$this->action = $action;
	}

	function setObjectType($type) {
		$this->object_type = $type;
	}

	function setLanguage($lan) {
		$this->language = $lan;
	}

	function serialize() {
		return array(
			'action' => $this->action,
			'object_type' => $this->object_type,
			'lang' => $this->language,
		);
	}

	function unserialize() {
	}
}
