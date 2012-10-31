<?php

class PDOFactory {
	public static function factory($db_name) {
		static $dbs = array();

		$return = null;

		//ensure one db object per db server
		if (isset($dbs[$db_name])) {
			return $dbs[$db_name];
		}

		$dbconfig = Config::get('db.'. $db_name);

		if (isset($dbconfig['driver'])) {
			$dbs[$db_name] = $return = new PDO(
				"{$dbconfig['driver']}:host={$dbconfig['server']};dbname={$dbconfig['database']}",
				isset($dbconfig['username']) ? $dbconfig['username'] : null,
				isset($dbconfig['password']) ? $dbconfig['password'] : null,
				array(
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
				)
			);
		}
		else {
			throw new RuntimeException('Unable to initialize DB object, no config found for db: '. $db_name);
		}

		if (!$return || !$return instanceof DB) {
			throw new Exception('Unable to instantiate DB object for - '. $db_name .': '. var_export($dbconfig, true));
		}

		return $return;
	}
}
