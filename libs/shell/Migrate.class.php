<?php

require_once PATH_LIBS .'stream/StreamTokenizer.class.php';
require_once PATH_LIBS .'stream/FileStream.class.php';
require_once PATH_DB .'DB.class.php';
require_once PATH_DB .'Query.class.php';

class Migrate extends Shell {
	const DIRECTION_UP = 0x01;
	const DIRECTION_DOWN = 0x02;

	private $migration = array();

	private $table = '__migrations';

	/**
	 * run all migrations
	 */
	public function _default() {
		while ($this->up())
			echo PHP_EOL . '=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-' . PHP_EOL;
	}

	/**
	 * run the single next migration
	 *
	 * @returns bool - true on successful migration, false if not (no migration etc.)
	 */
	public function up() {
		try {
			$version = $this->getCurrentVersion();
		}
		catch (Exception $e) {
			echo $e->getMessage() . PHP_EOL;
			return false;
		}

		$migrations = $this->getMigrations();

		if ($version === null) {
			$i = null;
		}
		else {
			$i = array_search($version, $migrations);
		}

		try {
			echo 'Current migration: ';
			if ($i === null)
				echo 'Nothing migrated yet'. PHP_EOL;
			else {
				echo PHP_EOL;
				$meta = $this->getMigrationMeta($i);
				$this->displayMigrationMeta($meta);
			}

			echo PHP_EOL;

			$next = $i === null ? 0 : $i + 1;
			if ($next < count($migrations)) {
				echo 'Next migration: '. PHP_EOL;

				$meta = $this->getMigrationMeta($next);
				$this->displayMigrationMeta($meta);

				$this->performMigration($meta);
			}
			else {
				echo "UP TO DATE!" . PHP_EOL;
				return false;
			}
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}

		return true;
	}

	public function down() {
		try {
			$version = $this->getCurrentVersion();
		}
		catch (Exception $e) {
			echo $e->getMessage() . PHP_EOL;
			return false;
		}

		$migrations = $this->getMigrations();

		if ($version === null) {
			echo 'Nothing migrated yet'. PHP_EOL;
			return false;
		}
		else {
			$i = array_search($version, $migrations);
		}

		try {
			echo 'Current migration: ';
			echo PHP_EOL;
			$meta = $this->getMigrationMeta($i);
			$this->displayMigrationMeta($meta);

			echo PHP_EOL;

			$meta = $this->getMigrationMeta($i, self::DIRECTION_DOWN);
			$this->displayMigrationMeta($meta);

			$this->performMigration($meta, self::DIRECTION_DOWN);
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}

		return true;
	}

	public function help() {
	}

	private function getMigrations() {
		$dir = scandir(PATH_CONFIG .'migrations/');

		$migrations = array();
		
		foreach ($dir as $d)
			if (is_numeric($d))
				$migrations[(int)$d] = $d;

		sort($migrations);

		return $migrations;
	}

	private function migrationNumberToPath($migration, $direction = self::DIRECTION_UP) {
		return PATH_CONFIG .'migrations/'. $migration .'/'. ($direction === self::DIRECTION_UP ? 'up' : 'down') .'.sql';
	}

	private function displayMigrationMeta($meta) {
		echo 'Migration version: '. $meta['migration'] . PHP_EOL;
		echo $meta['title'] . PHP_EOL;
		echo (isset($meta['description']) ? $meta['description'] . PHP_EOL : '');
		echo PHP_EOL;
		echo 'Database Handle: '. $meta['handle'] . PHP_EOL;
		echo PHP_EOL;
	}

	private function getMigrationMeta($migration, $direction = self::DIRECTION_UP) {
		$path = $this->migrationNumberToPath($migration, $direction);

		return array(
			'migration' => $migration,
			'path' => $path
			) + $this->parseMigrationMeta($path);
	}

	private function getCurrentVersion() {
		$db = DB::factory(Config::get('migrations.default_db'));

		if (!$db->query(new Query('SHOW CREATE TABLE '. $this->table))) {
			$db->query(
				new Query('CREATE TABLE '. $this->table .' (
	id int unsigned AUTO_INCREMENT,
	version int unsigned NOT NULL,
	migrated_ts DATETIME NOT NULL,

	title varchar(255) NOT NULL,
	description varchar(255) NOT NULL,
	db_handle varchar(32) NOT NULL,
	status tinyint unsigned NOT NULL,
	direction varchar(4) NOT NULL default \'\',

	PRIMARY KEY (id),
	KEY (version)
)'
				)
			);

			return null;
		}
		else {
			$errors = $db->select(new Query('
					SELECT *
					FROM '. $this->table .'
					WHERE status = 0
					ORDER BY migrated_ts'));

			if ($errors) {
				//TODO this is a strange way to handle the overrides
				if ($this->params->getOption('force')) {
					echo 'Failed Migrations Found!'. PHP_EOL . var_export($errors, true);

					echo '    Forcing migration'. PHP_EOL;
				}
				else
					throw new Exception('Failed Migrations Found!'. PHP_EOL . var_export($errors, true));
			}

			$version = $db->selectOne(new Query('
					SELECT version
					FROM '. $this->table .'
					ORDER BY version DESC
					LIMIT 1'));

			if (!$version)
				return null;
			else
				return $version['version'];
		}
	}

	protected function parseMigrationMeta($filename) {
		$file = new FileStream($filename);

		$tokenizer = new StreamTokenizer();

		$migration = $tokenizer->tokenize($file);
		$comment = null;

		foreach ($migration as $token) {
			if ($token['type'] == 'comment') {
				$comment = implode("\n", preg_replace('/^\s*[\*#\/]*\s*/', '', explode("\n", $token['value'])));
				break;
			}
		}

		if ($comment) {
			$meta = parse_ini_string($comment);

			//Missing required fields throws an exception
			if (!isset($meta['title'], $meta['handle']))
				throw new Exception('Invalid migration file: '. $filename .'; title and db handle are required');

			//Missing optional fields sets up default null values
			if (!isset($meta['description']))
				$meta['description'] = null;

			return $meta;
		}
		else
			throw new Exception('Invalid migration file: '. $filename .'; you must write a comment block defining title and db handle');
	}

	protected function performMigration($meta, $direction = self::DIRECTION_UP) {
		try {
			$db = DB::factory($meta['handle']);

			if ($direction == self::DIRECTION_UP) {
				$query = new Query('
					INSERT INTO '. $this->table .'
					(version, migrated_ts, title, description, db_handle, status, direction)
					values
					($$0, now(), $$1, $$2, $$3, $$4, "up")', $meta['migration'], $meta['title'], $meta['description'], $meta['handle']);

				if ($db->multiQuery($meta['path']))
					$db->query($query->addArgument(array(4 => true)));
				else {
					$db->query($query->addArgument(array(4 => false)));
					throw new Exception('Migration Failed!');
				}
			}
			else {

				if ($db->multiQuery($meta['path']))
					$db->query(new Query('DELETE FROM '. $this->table .' WHERE version = $$0', $meta['migration']));
				else {
					$error = var_export($db->error(), true);

					$db->query(new Query('
						INSERT INTO '. $this->table .'
						(version, migrated_ts, title, description, db_handle, status, direction)
						values
						($$0, now(), $$1, $$2, $$3, 0, "down")', $meta['migration'], $meta['title'], $meta['description'], $meta['handle']));
					throw new Exception('Migration Failed!'. PHP_EOL . $error . PHP_EOL);
				}
			}
		}
		catch (Exception $e) {
			echo $e;
		}
	}
}

?>
