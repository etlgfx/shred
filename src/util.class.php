<?php

namespace Shred;

/** @class Util
 *
 * Collection of static utility methods meant to be used across the
 * code base
 */
class Util {
	const SALT = 'f08ab5dc8bd51087d13e668b9c9e698a';

	/**
	 * encode a password using the two string arguments, if no salt given, one is generated
	 *
	 * @param string $password
	 * @param string $salt optional
	 *
	 * @return string password or false on bad param
	 */
	public static function encodePassword($password, $salt = null) {
		if ($salt === null || !is_string($salt))
			$salt = substr(sha1(rand(0, 65535) . rand(0, 65535)), 0, 8);
		else if (strlen($salt) > 8)
			$salt = substr($salt, 0, 8);
		else if (strlen($salt) < 8)
			return false;

		return $salt . sha1($salt . self::SALT . $password);
	}

	/**
	 * verify the given password with the db password (re-encode using salt)
	 *
	 * @param string $userPassword string given by user
	 * @param string $dbPassword string stored in DB
	 *
	 * @see encodePassword
	 *
	 * @return bool true if correct
	 */
	public static function verifyPassword($userPassword, $dbPassword) {
		return $dbPassword === self::encodePassword($userPassword, $dbPassword);
	}

	/**
	 * random UID
	 */
	public static function randomUid() {
		return Util::encodePassword(
			rand(0, 65535) .
			rand(0, 65535) .
			rand(0, 65535) .
			rand(0, 65535) .
			rand(0, 65535) .
			rand(0, 65535) .
			rand(0, 65535) .
			rand(0, 65535)
		);
	}

	/**
	 * Recursively Convert an associative array to an XML document
	 *
	 * @param array $data the associative array to convert
	 * @param DomDocument $dom the document you wish to add in to
	 * @param DomNode $node the dom node to use as root
	 *
	 * @returns DomDocument
	public static function arrayToXML(array $data, DomDocument $dom = null, DomNode $node = null) {
		if (!$dom) {
			$dom = new DomDocument('1.0', 'UTF-8');
			$node = $dom->appendChild($dom->createElement('root'));
		}
		else if (!$node) {
			$node = $dom->documentElement;
		}

		foreach ($data as $k => $v) {
			if (is_int($k))
				$child = $node->appendChild($dom->createElement($node->nodeName));
			else
				$child = $node->appendChild($dom->createElement($k));

			if (is_array($v))
				self::arrayToXML($v, $dom, $child);
			else if (is_string($v) || is_numeric($v))
				$child->appendChild($dom->createTextNode($v));
			else if ($v == null);
			else
				throw new Exception("Attempting to add WTF to xml tree: ". var_export($v, true));
		}

		return $dom;
	}
	 */

	/**
	 * generate a random hash for a new image
	 *
	 * @returns string 48 char hash
	 */
	public static function generateHash() {
		return self::encodePassword(microtime(true) . rand(0, 65535) . self::SALT);
	}

	/**
	 * attempts to return the default file extension for the given mime type
	 * TODO convert this to an associative array probably, be easier code
	 *
	 * @param string $mime
	 *
	 * @returns string e.g. ".jpg"
	 */
	public static function mimeToExtension($mime = null) {
		switch ($mime) {
			case 'image/jpeg':
			case 'image/jpg':
				return '.jpg';

			case 'image/png':
				return '.png';

			case 'image/gif':
				return '.gif';

			case 'image/tiff':
				return '.tiff';

			case 'audio/ogg':
			case 'audio/vorbis':
				return '.ogg';

			case 'audio/mpeg':
				return '.mp3';

			case 'image/x-icon':
				return '.ico';

			case 'application/pdf':
				return '.pdf';

			default:
				return null;
		}
	}

	/**
	 * convert a string like "bla_stuff" to "BlaStuff" to be used as a class
	 * name
	 *
	 * @param string $string
	 *
	 * @throws InvalidArgumentException on invalid parameter
	 *
	 * @returns string
	 */
	public static function toClassName($string = null) {
		if ($string === null)
			return null;

		if (!is_string($string))
			throw new \InvalidArgumentException('Invalid parameter passed string: '. $string);

		$class = '';
		
		foreach (preg_split('/[_-]/', $string, null, PREG_SPLIT_NO_EMPTY) as $part)
			$class .= ctype_upper($part[0]) ? $part : ucfirst(strtolower($part));

		return $class;
	}

	/**
	 * convert a string like "_bla_stuff" to "_blaStuff" to be used as a method
	 * name
	 *
	 * @param string $string
	 *
	 * @throws InvalidArgumentException on invalid parameter
	 *
	 * @returns string
	 */
	public static function toMethodName($string = null) {
		if ($string === null)
			return null;

		if (!is_string($string))
			throw new \InvalidArgumentException('Invalid parameter passed string: '. $string);

		$method = '';
		
		$first = true;

		foreach (preg_split('/[_-]/', $string) as $part) {
			if ($part) {
				$part = strtolower($part);

				if ($first) {
					$method .= $part;
					$first = false;
				}
				else
					$method .= ucfirst($part);
			}
			else if (!$part && $first && $method != '_')
				$method .= '_';
		}

		return $method;
	}

	/**
	 * convert a CamelCaseClassName to lower_case_underscores
	 *
	 * @param string $string
	 *
	 * @throw InvalidArgumentException if string is not a string
	 *
	 * @return string
	 */
	public static function fromClassName($string) {
		if (!is_string($string))
			throw new \InvalidArgumentException('Invalid parameter passed string: '. $string);

		$str = '';
		$last_upper = true;

		for ($i = 0; $i < strlen($string); $i++) {
			$c = $string[$i];

			if (ctype_upper($c)) {
				if ($last_upper) {
					$str .= strtolower($c);
				}
				else {
					$str .= '_'. strtolower($c);
					$last_upper = true;
				}
			}
			else if ($c == '_') {
				$str .= $c;
				$last_upper = true;
			}
			else {
				$str .= $c;
				$last_upper = false;
			}
		}

		return $str;
	}

	/**
	 * This method converts any string to a slug, optionally taking max_length 
	 * into account. A slug is of the format: [a-z0-9]+(-[a-z0-9]+)*
	 *
	 * e.g does not begin or end with hyphens, only has a single dash between 
	 * alpha numeric characters
	 *
	 * @param string  $string      the string you wish to convert to a slug
	 * @param int     $max_length  the maximum length you need, if a hyphen 
	 *                             results as the last character it is also 
	 *                             trimmed off to meet the format requirement
	 *
	 * @return string
	 *
	 * @throw InvalidArgumentException
	 */
	public static function toSlug($string, $max_length = null) {
		if (!is_string($string) || !$string)
			throw new \InvalidArgumentException('Invalid argument string');
		if ($max_length && !is_numeric($max_length))
			throw new \InvalidArgumentException('Invalid argument max_length');
		else if ($max_length)
			$max_length = (int)$max_length;

		$slug = preg_replace('#[^a-z0-9]#', '-', strtolower($string));
		$slug = implode('-', preg_split('#-#', $slug, null, PREG_SPLIT_NO_EMPTY));

		if ($max_length > 0)
			return trim(mb_strcut($slug, 0, $max_length), '-');
		else
			return $slug;
	}

	/**
	 * Generate a unique & random temporary filename in the requested directory.
	 * Defaults to /tmp/. You can add custom prefixes and suffixes and specify
	 * whether to nest the directories or not.
	 *
	 * e.g. Util::tempFile('/tmp/', 'pre_', '.jpg', true) returns something
	 * like: /tmp/abc/def/pre_a1d2f3e4.jpg
	 *
	 * @param string $directory
	 * @param string $prefix
	 * @param string $suffix
	 * @param boolean $nest
	 *
	 * @throws InvalidArgumentException
	 *
	 * @returns string absolute path
	 */
	public static function tempFile($directory = null, $prefix = null, $suffix = null, $nest = true, $touch = false) {
		if (!$directory) {
			$directory = sys_get_temp_dir();
		}

		if (!is_string($directory) || !is_writable($directory) || !is_dir($directory)) {
			throw new \InvalidArgumentException('Directory is not writable');
		}

		if ($directory[strlen($directory) - 1] != '/') {
			$directory .= '/';
		}

		if ($prefix) {
			if (!is_string($prefix)) {
				throw new \InvalidArgumentException('Invalid prefix passed, must be a string');
			}
		}
		else {
			$prefix = '';
		}

		if ($suffix) {
			if (!is_string($suffix)) {
				throw new \InvalidArgumentException('Invalid suffix passed, must be a string');
			}
		}
		else {
			$suffix = '';
		}

		$fh = false;
		$path = false;

		while (!$fh) {
			if ($nest) {
				$str = self::randomUid();

				$path = substr($str, 0, 2) .'/'. substr($str, 2, 2) .'/'. substr($str, 4) . $suffix;

				if ($prefix) {
					$path = $prefix .'/'. $path;
				}

				$path = $directory . $path;

				$dir = dirname($path);

				if (!is_dir($dir)) {
					mkdir($dir, 0755, true);
				}
			}
			else {
				$path = $directory . $prefix . self::randomUid() . $suffix;
			}

			if ($touch)
				$fh = fopen($path, 'x');
			else if (!file_exists($path))
				break;
		}

		if ($fh)
			fclose($fh);

		return $path;
	}
}

