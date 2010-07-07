<?php

require_once PATH_LIBS .'stream/Stream.class.php';

class FileStream extends Stream {
	private $file;
	private $current;

	public function __construct($filename, $strip_spaces = true) {
		parent::__construct($strip_spaces);

		if (!is_string($filename) || !file_exists($filename))
			throw new Exception("Couldn't open file: ". var_export($filename, true));

		$this->file = fopen($filename, 'r');
		$this->current = null;

		if (!$this->file)
			throw new Exception("Couldn't open file handle: ". $filename);
	}

	public function inStream() {
		return !feof($this->file);
	}

	public function rewindOne() {
		fseek($this->file, -1, SEEK_CUR);
	}

	public function rewind($num) {
		fseek($this->file, -$num, SEEK_CUR);
	}

	public function getChar() {
		$this->current = $this->inStream() ? fgetc($this->file) : null;

		return $this->current;
	}

	public function getCurrentChar() {
		return $this->current;
	}

	public function close() {
		if ($this->file)
			return fclose($this->file);

		return true;
	}
}
