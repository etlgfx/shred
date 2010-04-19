<?php

require_once PATH_LIBS .'ConfigInterface.class.php';

class ACMConfig implements ConfigInterface {
	const SERVER_URL = 0x01;
	const APP_ID = 0x02;
	const APP_KEY = 0x03;

	private $config;
	private $site;

	private function __construct($site, array $args) {
		$this->site = $site;
		$this->config = $args;
	}

	public static function factory($site) {
		if (is_string($site) && ctype_alnum($site)) {
			$path = PATH_CONFIG . $site .'.inc.php';
			if (file_exists($path)) {
				return new ACMConfig($site, require($path));
			}
			else {
				throw new Exception("Error opening config file: ". $path);
			}
		}
		else {
			throw new Exception("Invalid parameter: ". var_export($site, true));
		}
	}

	public function getURL() {
		return $this->config[self::SERVER_URL];
	}

	public function getAppId() {
		return $this->config[self::APP_ID];
	}

	public function getAppKey() {
		return $this->config[self::APP_KEY];
	}

	public function getSite() {
		return $this->site;
	}
}
