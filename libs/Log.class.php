<?php

/**
 * @class Log
 *
 * Singleton Log class, this is the be all end all of error handling for the framework
 */
class Log {
	/* TODO test the new bitmask values!!! */
	const APP_ERROR	   = 0x101;
	const APP_WARNING	 = 0x102;
	const APP_NOTICE	  = 0x104;
	const APP_SUCCESS	 = 0x108;
	const APP_ERROR_GROUP = 0x100;

	const USER_ERROR	   = 0x201; //user errors (didn't fill out form field etc.)
	const USER_WARNING	 = 0x202; //
	const USER_NOTICE	  = 0x204; //positive feedback messages
	const USER_SUCCESS	 = 0x208; //positive feedback messages
	const USER_ERROR_GROUP = 0x200;

	const LVL_ERROR   = 0x01;
	const LVL_WARNING = 0x02;
	const LVL_NOTICE  = 0x04;
	const LVL_SUCCESS = 0x08;

	const ERROR_TYPE_GENERIC = 0x00001000;
	const ERROR_TYPE_DB	  = 0x00002000;
	const ERROR_TYPE_MOD	 = 0x00004000; //model / module
	const ERROR_TYPE_VIEW	= 0x00008000;
	const ERROR_TYPE_CTRL	= 0x00010000; //controller
	const ERROR_TYPE_PERM	= 0x00020000; //permissions
	const ERROR_TYPE_GROUP   = 0xFFFFF000;

	const I_MSG  = 0x01;
	const I_TYPE = 0x02;
	const I_LVL  = 0x04;

	private $errors;
	private $phplog;
	private $user_errors;

	private static $logs = array(
		self::ERROR_TYPE_DB => 'db.log'
	);

	/**
	 * @return Error Singleton instance 
	 */
	public static function & inst() {
		static $instance = null;

		if ($instance === null)
			$instance = new Log();

		return $instance;
	}

	/**
	 * private constructor
	 */
	private function __construct() {
		$this->errors = array();
		$this->phplog = ini_get('error_log');
		$this->phpreporting = ini_get('error_reporting');
		$this->user_errors = false;
	}

	/**
	 * raise an error message, if it's app type error log it to php log
	 *
	 * @param string $message
	 * @param enum $level enum of error level constants
	 * @param enum $type enum of error type constants
	 *
	 * @return void
	 */
	public static function raise($message, $level = self::APP_WARNING, $type = self::ERROR_TYPE_GENERIC) {
		$inst = self::inst();

		$inst->errors []= array(
			self::I_MSG => $message instanceof Exception ? $message->getMessage() : $message,
			self::I_LVL => $level,
			self::I_TYPE => $type
		);

		if ($level & self::USER_ERROR_GROUP)
			$inst->user_errors = true;

		if ($level & self::APP_ERROR_GROUP) {
			$level_str = self::getErrorLevel($level);
			$type_str = self::getErrorType($type);

			switch ($level) {
				case self::APP_ERROR:
					$level = E_USER_ERROR;
					break;
				case self::APP_NOTICE:
					$level = E_USER_NOTICE;
					break;
				default:
					$level = E_USER_WARNING;
					break;
			}

			if ($message instanceof Exception) {
				$trace = $message->getTrace();
				$message = 'Exception: '. get_class($message) .'; '. $message->getMessage() .'; '. $trace[0]['class'] . $trace[0]['type'] . $trace[0]['function'] .' - '. $message->getFile() .'@'. $message->getLine();
			}
			else {
				$trace = debug_backtrace(false);
				$message = $message .'; '. $trace[1]['class'] .'::'. $trace[1]['function'] .' - '. $trace[0]['file'] .'@'. $trace[0]['line'];
			}

			$file = $inst->phplog;
			if (isset(self::$logs[$type]))
				$file = PATH_CODE .'logs/'. self::$logs[$type];

			if ($file)
				file_put_contents($file, '['. date('Y-m-d H:i:s', time()) .'] '. $type_str .' '. $level_str .' error: '. $message . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * grab all user errors raised during runtime in XML form
	 *
	 * @see getUserErrors
	 *
	 * @return DomDocument
	 */
	public function getUserErrorsXML() {
		$dom = new DomDocument('1.0', 'UTF-8');
		$root = $dom->appendChild($dom->createElement('errors'));

		$errors = $this->getUserErrors();

		foreach ($errors as $k => $error) {
			$node = $root->appendChild($dom->createElement('error'));
			$node->setAttribute('level', self::getErrorLevel($error[self::I_LVL]));
			$node->setAttribute('type', self::getErrorType($error[self::I_MSG]));
			$node->setAttribute('index', $k);
			$node->appendChild($dom->createTextNode($error[self::I_MSG]));
		}

		return $dom;
	}

	/**
	 * grab all errors raised during runtime
	 *
	 * @return array //TODO return an iterator instead??
	 */
	public static function getErrors() {
		return self::inst()->errors;
	}

	/**
	 */
	public function getUserErrorsArray() {
		$errors = array('success' => array(), 'error' => array());

		foreach ($this->errors as $k => $error) {
			$index = 'error';
			if ($error[self::I_LVL] & self::LVL_SUCCESS)
				$index = 'success';

			$errors[$index] []= array(
				'message' => $error[self::I_MSG],
				'type' => self::getErrorType($error[self::I_MSG]),
				'level' => self::getErrorLevel($error[self::I_LVL]),
				'index' => $k,
			);
		}

		if (!$errors['success'])
			unset($errors['success']);
		if (!$errors['error'])
			unset($errors['error']);

		return $errors;
	}

	/**
	 * grab only user level errors for message boxes to the user
	 *
	 * @return array 
	 */
	protected function getUserErrors() {
		$errors = array();

		foreach ($this->errors as $k => $error)
			if ($error[self::I_LVL] & self::USER_ERROR_GROUP)
				$errors[$k] = $error;

		return $errors;
	}

	/**
	 * return whether any user facing errors were generated at all
	 *
	 * @returns boolean
	 */
	public function hasUserErrors() {
		return $this->user_errors;
	}

	/**
	 * Convert the currently raised errors to plain text string representation
	 *
	 * @returns string
	 */
	public function __toString() {
		$str = '';

		foreach ($this->errors as $error)
			$str .= self::getErrorLevel($error[self::I_LVL]) .': '. self::getErrorType($error[self::I_TYPE]) .' - '. $error[self::I_MSG] ."\n";

		return $str;
	}

	/**
	 * Grab a human readable string representation of the type of error we're
	 * talking about
	 *
	 * @param int $type error type to convert
	 *
	 * @returns string
	 */
	private static function getErrorType($type) {
		switch ($type) {
			case self::ERROR_TYPE_GENERIC:
				return 'generic';
			case self::ERROR_TYPE_DB:
				return 'database';
			case self::ERROR_TYPE_MOD:
				return 'model';
			case self::ERROR_TYPE_VIEW:
				return 'view';
			case self::ERROR_TYPE_CTRL:
				return 'controller';
			case self::ERROR_TYPE_PERM:
				return 'permission';
			default:
				return 'unknown';
		}
	}

	/**
	 * Grab a human readable string representation of the level of the error
	 *
	 * @param int $level error level
	 *
	 * @returns string
	 */
	private static function getErrorLevel($level) {
		switch ($level) {
			case self::USER_ERROR:
				return 'error';
			case self::APP_ERROR:
				return 'fatal';
			case self::USER_WARNING:
			case self::APP_WARNING:
				return 'warning';
			case self::USER_NOTICE:
			case self::APP_NOTICE:
				return 'notice';
			case self::USER_SUCCESS:
			case self::APP_SUCCESS:
				return 'success';
			default:
				return 'unknown';
		}
	}
}

