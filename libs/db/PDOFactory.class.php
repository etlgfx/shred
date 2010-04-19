<?php

final class PDOFactory {
	private static $pdo;

	public static function & factory($handle) {
		if (!isset(self::$pdo))
			self::$pdo = array();

		if (preg_match('/^\w+$/', $handle)) {
			if (isset(self::$pdo[$handle])) {
				return self::$pdo[$handle];
			}

			$conf = Conf::get($handle);

			if (is_array($conf) && isset(
					$conf[CONF_KEY_DBTYPE],
					$conf[CONF_KEY_DBHOST],
					$conf[CONF_KEY_DBNAME],
					$conf[CONF_KEY_DBUSER],
					$conf[CONF_KEY_DBPASS]
					)) {

				try {
					self::$pdo[$handle] = new PDO("{$conf[CONF_KEY_DBTYPE]}:dbname={$conf[CONF_KEY_DBNAME]};host={$conf[CONF_KEY_DBHOST]}", $conf[CONF_KEY_DBUSER], $conf[CONF_KEY_DBPASS]);
				}
				catch (PDOException $e) {
					throw new Exception('Connection failed: '. $e->getMessage());
				}

				return self::$pdo[$handle];
			}
			else {
				throw new Exception('Error opening config file: '. var_export($path, true));
			}
		}
		else {
			throw new Exception('Invalid parameter: '. var_export($handle, true));
		}
	}
}
