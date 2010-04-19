<?php

require_once PATH_DB .'DBDescriptor.class.php';

class DBConf {
	private $dbs;

	public static function & instance() {
		static $instance = null;

		if ($instance === null)
			$instance = new DBConf();

		return $instance;
	}

	private function __construct() {
		$this->dbs = array();
	}

	public static function addDescriptor(DBDescriptor $descriptor) {
		$inst = self::instance();

		$name = $descriptor->getDescriptorName();

		if (!defined('DB_'. strtoupper($name)))
			define('DB_'. strtoupper($name), $name);

		$inst->dbs[$name] = $descriptor;

		return true;
	}

	public static function getDescriptor($descriptor_name) {
		$inst = self::instance();
		if (isset($inst->dbs[$descriptor_name]))
			return $inst->dbs[$descriptor_name];
		else
			return false;
	}
}

