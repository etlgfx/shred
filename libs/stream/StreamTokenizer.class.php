<?php

require_once PATH_LIBS .'stream/Stream.class.php';

class StreamTokenizer {

	private $literals = array('"', '`', "'");
	private $escape = '\\';
	private $comments = array('--', '#', array('/*', '*/'));
	private $stream = null;

	private $literal_callback;
	private $comment_callback;
	private $text_callback;

	const MODE_COMMENT = 0x01;
	const MODE_LITERAL = 0x02;
	const MODE_ESCAPE  = 0x04;
	const MODE_NORMAL  = 0x00;

	public function __construct() {
		$this->comment_callback = array($this, 'defaultCallback');
		$this->text_callback = array($this, 'defaultCallback');
		$this->literal_callback = array($this, 'defaultCallback');
	}

	public function tokenize(Stream $stream) {
		$this->stream = $stream;

		$mode = self::MODE_NORMAL;

		$line = 1;
		$char = 0;
		$str = '';
		$token = null;

		while ($stream->inStream()) {
			$c = $stream->getChar();
			$char++;

			if ($c == "\n") {
				$line++;
				$char = 0;
			}

			switch ($mode) {
				case self::MODE_COMMENT:
					if (is_array($token)) {
						if ($token == $this->matchToken($this->comments, $c, false)) {
							call_user_func($this->comment_callback, $token[0] . $str . $token[1]);

							$mode = self::MODE_NORMAL;
							$token = null;
							$str = '';

							continue 2;
						}
					}
					else if ($c == "\n") {
						call_user_func($this->comment_callback, $token . $str . $c);

						$mode = self::MODE_NORMAL;
						$token = null;
						$str = '';

						continue 2;
					}
					break;

				case self::MODE_LITERAL:
					/*if escape else*/
					if ($c == $token) {
						call_user_func($this->literal_callback, $token . $str . $c);

						$mode = self::MODE_NORMAL;
						$token = null;
						$str = '';

						continue 2;
					}
					break;

				case self::MODE_ESCAPE:
					break;

				default:
					if (in_array($c, $this->literals)) {
						call_user_func($this->text_callback, $str);

						$mode = self::MODE_LITERAL;
						$token = $c;
						$str = '';

						continue 2;
					}
					else if ($token = $this->matchToken($this->comments, $c, true)) {
						call_user_func($this->text_callback, $str);

						$mode = self::MODE_COMMENT;
						$str = '';

						continue 2;
					}

					//else if
			}

			$str .= $c;
		}

		//return $str;
		$this->stream = null;
	}

	public function commentCallback(array $callback) {
		$this->comment_callback = $callback;
	}

	public function textCallback(array $callback) {
		$this->text_callback = $callback;
	}

	public function literalCallback(array $callback) {
		$this->literal_callback = $callback;
	}

	public function defaultCallback($str) {
		echo $str;
	}

	private function matchToken($tokens, $c, $open = true) {
		$token_index = $open == true ? 0 : 1;
		$match = null;

		foreach ($tokens as $token) {

			if ($c == $token) {
				return $token;
			}
			else if (is_string($token) && strlen($token) > 1 && $c == $token[0]) {
				$match = $token;
			}
			else if (is_array($token)) {
				if ($c == $token[$token_index]) {
					return $token;
				}
				else if (strlen($token[$token_index]) > 1 && $c == $token[$token_index][0]) {
					$match = $token[$token_index];
				}
			}

			if ($match) {
				$str = $c;

				$l = strlen($match) - 1;

				for ($i = 0; $i < $l; $i++) {
					$str .= $this->stream->getChar();
				}

				if ($str == $match)
					return $token;
				else
					$this->stream->rewind($l);
			}
		}

		return null;
	}
}

?>
