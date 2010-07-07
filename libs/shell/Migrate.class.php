<?php

require_once PATH_LIBS .'stream/StreamTokenizer.class.php';
require_once PATH_LIBS .'stream/FileStream.class.php';

class Migrate extends Shell {
	const DIRECTION_UP = 0x01;
	const DIRECTION_DOWN = 0x02;

	//protected $required = array('one');

	public function _default() {
		//$this->parseMigration('config/migrations/0_initial_import.sql');

		$this->parseMigration('testmigration.sql');
	}

	public function up() {
	}

	public function down() {
	}

	public function help() {
	}

	protected function getMigrations() {
	}

	protected function getCurrentVersion() {
	}

	protected function parseMigration($file, $direction = self::DIRECTION_UP) {
		$file = new FileStream($file);

		$tokenizer = new StreamTokenizer();
		echo $tokenizer->tokenize($file);

		if ($direction == self::DIRECTION_UP) {
		}
		else if ($direction == self::DIRECTION_DOWN) {
		}
	}

}

?>
