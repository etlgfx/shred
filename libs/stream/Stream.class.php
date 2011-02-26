<?php

abstract class Stream {
	private $strip_spaces;

	protected function __construct($strip_spaces = true) {
		$this->strip_spaces = $strip_spaces;
	}

	public abstract function inStream();

	public abstract function rewindOne();

	public abstract function rewind($num);

	public abstract function getChar();

	public abstract function getCurrentChar();

	public abstract function close();

	public function getWord(&$str) {
		if (!$this->inStream())
			return null;

		$count = 0;
		$str = '';

		while ($this->inStream()) {
			$c = $this->getChar();
			$count++;

			if (!$str) {
				if ($this->strip_spaces && ctype_space($c))
					continue;

				$str .= $c;

				if (ctype_space($c) || ctype_punct($c))
					break;
			}
			else if ($this->strip_spaces && ctype_space($c))
				break;
			else if (! (ctype_space($c) || ctype_punct($c)))
				$str .= $c;
			else {
				$this->rewindOne();
				$count--;
				break;
			}
		}

		return $count;
	}

	public function getQuoted(&$str) {
		if (!$this->inStream())
			return null;

		$str = '';
		$q = '';
		$escape = false;
		$rewind = 0;

		while ($this->inStream()) {
			$c = $this->getChar();
			$rewind++;

			if (!$q && ctype_space($c))
				continue;
			else if (!$q && ($c == '"' || $c == "'")) {
				$q = $c;
				continue;
			}
			else if (!$q)
				break;
			else if ($escape) {
				if ($c == '\\')
					$str .= $c;
				else if ($c == 'n')
					$str .= "\n";
				else if ($c == 'i')
					$str .= "		";
				else if ($c == $q)
					$str .= $q;
				else {
					$this->rewind($rewind);
					return null;
				}
				$escape = false;
			}
			else if ($c == '\\') {
				$escape = true;
				continue;
			}
			else if ($c == $q)
				break;
			else
				$str .= $c;
		}

		return $rewind; //number of characters successfully grabbed
	}

	public function getWordToken(&$token) {
		$count = 0;
		$token = $this->getCurrentChar();

		if (!ctype_alpha($token))
			return null;

		while (ctype_alnum($c = $this->getChar())) {
			$count++;
			$token .= $c;
		}

		return $count;
	}
}

