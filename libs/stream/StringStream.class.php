<?php

class StringStream extends Stream {
	private $pos;
	private $end;
	private $string;

	public function __construct($string, $strip_spaces = true) {
		parent::__construct($strip_spaces);

		if (!$string || !is_string($string))
			throw new Exception("Invalid argument passed: string - ". var_export($string, true));

		$this->string = $string;
		$this->pos = 0;
		$this->end = strlen($string);
	}

	public function inStream() {
		return $this->pos < $this->end;
	}

	public function rewindOne() {
		$this->pos--;
	}

	public function rewind($num) {
		$this->pos -= $num;
	}

	public function getChar() {
		return $this->inStream() ? $this->string[$this->pos++] : null;
	}

	public function getCurrentChar() {
		return $this->inStream() ? $this->string[$this->pos - 1] : null;
	}

	public function close() {
		return true;
	}

}

