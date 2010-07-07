<?php

require_once PATH_LIBS .'stream/Stream.class.php';

class StreamTokenizer {

	private $literals = array('"', '`', "'");
	private $escape = '\\';
	private $comments = array('--', '#', array('/*', '*/'));

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

			echo $line .'-'. $char .': '. $c . PHP_EOL;

			switch ($mode) {
				case self::MODE_COMMENT:
					if (is_array($token)) {
					}
					else if ($c == "\n") {
						echo 'comment: '. $line .':'. $char .' ';
						call_user_func($this->comment_callback, $str . $c);
						$mode = self::MODE_NORMAL;
						$token = null;
						$str = '';
						echo PHP_EOL;
						continue;
					}
					break;
				case self::MODE_LITERAL:
					/*if escape else*/
					if ($c == $token) {
						echo 'literal: '. $line .':'. $char .' ';
						call_user_func($this->literal_callback, $str . $c);
						$mode = self::MODE_NORMAL;
						$token = null;
						$str = '';
						echo PHP_EOL;
						continue;
					}
					break;
				case self::MODE_ESCAPE:
					break;
				default:
					if (in_array($c, $this->literals)) {
						echo 'text: '. $line .':'. $char .' ';
						$token = $c;
						$mode = self::MODE_LITERAL;
						call_user_func($this->text_callback, $str);
						$str = '';
						echo PHP_EOL;
						continue;
					}
					else if (in_array($c, $this->comments)) {
						echo 'text: '. $line .':'. $char .' ';
						$token = $c;
						$mode = self::MODE_COMMENT;
						call_user_func($this->text_callback, $str);
						$str = '';
						echo PHP_EOL;
						continue;
					}
					//else if
			}

			$str .= $c;
		}

		//return $str;
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

}

?>
