<?php

require_once PATH_LIBS .'Log.class.php';

class Validator {

	protected $required;
	protected $rules;
	protected $fields;

	public function __construct(array $required_fields, array $rules) {
		$this->required = $required_fields ? $required_fields : array();
		$this->rules = array();

		foreach ($rules as $k => $v) {
			if (is_string($v)) {
				$v = array($v);
			}

			$v[0] = 'valid'. ucfirst(strtolower($v[0]));

			if (!method_exists($this, $v[0])) {
				throw new Exception("Invalid validation method requested: ". $v[0]);
			}

			$this->rules[$k] = $v;
		}

		$this->fields = array();

		foreach (array_keys(array_merge(array_flip($required_fields), $rules)) as $field) {
			$this->fields[$field] = $field;
		}
	}

	/**
	 * @param array $data
	 *
	 * @returns bool - true on success
	 */
	public function validate(array $data = null) {
		if ($data === null) {
			$data = array();
		}

		$return = true;

		$errors = array();

		foreach ($this->required as $r) {
			if (!isset($data[$r])) {
				$return = false;
				Log::raise('Required field missing: '. $r);

				$errors[$r] = true;
			}
		}

		foreach ($data as $field => $value) {
			if (!isset($this->rules[$field]) || $value === null) {
				continue;
			}

			if (!call_user_func_array(array($this, $this->rules[$field][0]), array($value) + $this->rules[$field])) {

				$return = false;

				if (!isset($errors[$field])) {
					if (isset($this->rules[$field]['message'])) {
						Log::raise($this->rules[$field]['message']);
					}
					else {
						Log::raise('Invalid input in field: '. $field);
					}
				}
			}
		}

		return $return;
	}

	/**
	 * make sure the data passed in is a valid integer (string or proper
	 * integer)
	 *
	 * @param mixed $data value to validate
	 * @param number|null $min null means skip the minimum test
	 * @param number|null $max null means skip the maximum test
	 *
	 * @return boolean
	 */
	protected function validInteger($data, $min = null, $max = null) {
		if (!is_int($data) && !ctype_digit($data)) {
			return false;
		}

		if ($min !== null && $min > $data) {
			return false;
		}

		if ($max !== null && $max < $data) {
			return false;
		}

		return true;
	}

	/**
	 * make sure the data passed in is a valid number, int or float (string or
	 * proper integer)
	 *
	 * @param mixed $data, value to validate
	 * @param number|null $min null means skip the minimum test
	 * @param number|null $max null means skip the maximum test
	 *
	 * @return boolean
	 */
	protected function validNumber($data, $min = null, $max = null) {
		if (!is_numeric($data)) {
			return false;
		}

		if ($min !== null && $min > $data) {
			return false;
		}

		if ($max !== null && $max < $data) {
			return false;
		}

		return true;
	}

	/**
	 * make sure the string passed in is a valid email address
	 *
	 * @param mixed $data, value to validate
	 *
	 * @return boolean
	 */
	protected function validEmail($data) {
		return true && preg_match("/^([a-z0-9_\.\-+])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i", $data);
	}

	/**
	 * make sure the string passed in is a valid url
	 *
	 * @param mixed $data, value to validate
	 *
	 * @return boolean
	 */
	protected function validUrl($data) {
		return true && preg_match('#^([a-z]+(\+[a-z]+)*://)?([a-z0-9-]+)(\.[a-z0-9-]+)*([/]+.*)?$#i', $data);
	}

	/*
	   protected function validPhone($data) {
	   }
	 */

	/**
	 * make sure the data passed in is a string, mainly to check strlen
	 *
	 * @param mixed $data, value to validate
	 * @param number|null $min null means skip the minimum test
	 * @param number|null $max null means skip the maximum test
	 *
	 * @return boolean
	 */
	protected function validString($data, $min = null, $max = null) {
		if (!is_string($data)) {
			return false;
		}

		$strlen = strlen($data);

		if ($min !== null && $min > $strlen) {
			return false;
		}

		if ($max !== null && $max < $strlen) {
			return false;
		}

		return true;
	}

	/**
	 * make sure the data passed in is an alpha string
	 *
	 * @param mixed $data, value to validate
	 * @param number|null $min null means skip the minimum test
	 * @param number|null $max null means skip the maximum test
	 *
	 * @return boolean
	 */
	protected function validAlpha($data, $min = null, $max = null) {
		if (!ctype_alnum($data)) {
			return false;
		}

		$strlen = strlen($data);

		if ($min !== null && $min > $strlen) {
			return false;
		}

		if ($max !== null && $max < $strlen) {
			return false;
		}

		return true;
	}

	/**
	 * make sure the data passed in is an alphanumeric string
	 *
	 * @param mixed $data, value to validate
	 * @param number|null $min null means skip the minimum test
	 * @param number|null $max null means skip the maximum test
	 *
	 * @return boolean
	 */
	protected function validAlnum($data, $min = null, $max = null) {
		if (!ctype_alnum($data)) {
			return false;
		}

		$strlen = strlen($data);

		if ($min !== null && $min > $strlen) {
			return false;
		}

		if ($max !== null && $max < $strlen) {
			return false;
		}

		return true;
	}

	/**
	 * make sure the data passed in is a hexadecimal string
	 *
	 * @param mixed $data, value to validate
	 * @param number|null $min null means skip the minimum test
	 * @param number|null $max null means skip the maximum test
	 *
	 * @return boolean
	 */
	protected function validHex($data, $min = null, $max = null) {
		if (!ctype_xdigit($data)) {
			return false;
		}

		$strlen = strlen($data);

		if ($min !== null && $min > $strlen) {
			return false;
		}

		if ($max !== null && $max < $strlen) {
			return false;
		}

		return true;
	}

	/**
	 * return the total list of fields this validator object is aware of
	 *
	 * @returns array
	 */
	public function fields() {
		return $this->fields;
	}
}

?>
