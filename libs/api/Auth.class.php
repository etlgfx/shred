<?php

require_once PATH_DB .'DB.class.php';
require_once PATH_API .'Util.class.php';

class Auth {
	private $id;
	private $locale_id;

	/**
	 * Initialize DB handle
	 */
	public function __construct() { }

	/**
	 * authenticate given login parameters
	 *
	 * @param $app_key string
	 * @param $app_secrect string
	 * @param $ip string (optional)
	 *
	 * @throws Exception on error
	 *
	 * @returns boolean true on success
	 */
	public function authenticate($app_key, $app_secret, $ip = null) {
		$db = DB::factory(DB_APP);

		$q = new Query('SELECT * FROM auth WHERE app_key = $$0 LIMIT 1');
		$row = $db->selectOne($q->addArgument($app_key));

		if (!$row || !Util::verifyPassword($app_secret, $row['app_secret_hash']))
			throw new Exception('App Key invalid');

		$this->id = (int)$row['id'];
		$this->locale_id = (int)$row['locale_id'];

		if ($ip) {
			$q = new Query('SELECT * FROM auth_ips WHERE auth_id = $$0');
			$res = $db->select($q->addArgument($row['id']));

			if (!$res) //no ip restriction found
				return true;

			foreach ($res as $row)
				if ($this->isIpAllowed($ip, $row['ip_cidr']))
					return true;

			throw new Exception('IP address not in range');
		}

		return true;
	}

	/**
	 * Returns the locale id associated with the authenticated record
	 *
	 * @returns int
	 */
	public function getLocale() {
		return $this->locale_id;
	}

	/**
	 * check whether the ip address given falls in acceptable range of the ip addresses given
	 *
	 * @param $ip string ip address to check
	 * @param $ip_cidr ip address CIDR string e.g. 192.168.0.1/27
	 *
	 * @returns bool
	 */
	private function isIpAllowed($ip, $ip_cidr) {
		list($test_ip, $bits) = explode('/', $ip_cidr);

		if ($bits == 32)
			return $ip == $test_ip;

		$ip = ip2long($ip);
		$test_ip = ip2long($test_ip);

		for ($i = (int)$bits; $i < 32; $i++) {
			$ip >>= 1;
			$test_ip >>= 1;
		}

		return $ip == $test_ip;
	}
}
