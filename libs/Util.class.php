<?php

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
			$salt = substr(md5(rand(0, 65535) . rand(0, 65535)), 0, 8);
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
	 * Recursively Convert an associative array to an XML document
	 *
	 * @param array $data the associative array to convert
	 * @param DomDocument $dom the document you wish to add in to
	 * @param DomNode $node the dom node to use as root
	 *
	 * @returns DomDocument
	 */
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
	public static function mimeToExtension($mime) {
		switch ($mime) {
			case 'image/jpeg':
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
	 * @throw Exception on invalid parameter
	 *
	 * @returns string
	 */
	public static function toClassName($string) {
		if (!is_string($string))
			throw new Exception('Invalid parameter passed string: '. $string);

		$class = '';
		
		foreach (explode('_', $string) as $part)
			$class .= ucfirst(strtolower($part));

		return $class;
	}

	/**
	 * convert a string like "_bla_stuff" to "_blaStuff" to be used as a method
	 * name
	 *
	 * @param string $string
	 *
	 * @throw Exception on invalid parameter
	 *
	 * @returns string
	 */
	public static function toMethodName($string) {
		if (!is_string($string))
			return null;

		$method = '';
		
		$first = true;

		foreach (explode('_', $string) as $part) {
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
}

?>
