<?php

class Conf {
	private $data;

	private function __construct() {
		$this->data = array();
	}

	private static function & inst() {
		static $instance = null;

		if ($instance === null)
			$instance = new Conf();

		return $instance;
	}

	public static function get($key) {
		$inst = self::inst();
		return isset($inst->data[$key]) ? $inst->data[$key] : null;
	}

	public static function set($key, $value) {
		self::inst()->data[$key] = $value;
	}
}
