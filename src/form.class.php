<?php

namespace Shred;

if (!defined('RE_URL'))
	define('RE_URL', '#^(http|ftp|https|svn)://([a-z][-a-z0-9\.]*[a-z0-9]\.)+[a-z]+#i');
if (!defined('RE_EMAIL'))
	define('RE_EMAIL', '#^[a-z][-a-z0-9\.]*[a-z0-9]@([a-z][-a-z0-9\.]*[a-z0-9]\.)+[a-z]+$#i');

class Form {
	private $fields;
	private $required_fields;
	private $field_map;

	private $current_page;
	private $page_count;
	private $method;
	private $action;
	private $valid;

	const REQUIRED = 0x01;
	const TYPE = 0x02;
	const VALIDATION = 0x03;
	const ERROR = 0x04;
	const VALID_VALUES = 0x05;

	const DISPLAY = 0x08;
	const DESCRIPTION = 0x09;
	const ERROR_MESSAGE = 0x0a;
	const DEFAULT_VALUE = 0x0b;

	const TYPE_SELECT = 0x10;
	const TYPE_RADIO = 0x11;
	const TYPE_CHECK = 0x12;
	const TYPE_INT = 0x13;
	const TYPE_NUMBER = 0x14;
	const TYPE_CHAR = 0x15;
	const TYPE_TEXT = 0x16;
	const TYPE_TEXTBOX = 0x17;
	const TYPE_PASSWORD = 0x18;
	const TYPE_CAPTCHA = 0x19;
	const TYPE_IMAGE = 0x1a;
	const TYPE_HIDDEN = 0x1b;
	const TYPE_BUTTON = 0x1c;
	const TYPE_SUBMIT = 0x1d;

	const METHOD_POST = 0x80;
	const METHOD_GET = 0x81;
	const METHOD_AJAX = 0x82;

	const AJAX_CALLBACK = 'ajaxFormSubmit'; //default JavaScript ajax callback for form submission

	public function __construct($method = self::METHOD_POST, $action = null, $ajax_callback = self::AJAX_CALLBACK) {
		$this->fields = array();
		$this->required_fields = array();
		$this->field_map = array();

		$this->current_page = 0;
		$this->page_count = 1;

		if (!is_int($method))
			throw new Exception("Invalid parameters");

		$this->method = $method;
		$this->action = $action ? $action : REQUEST_URI;
		$this->callback = $method == self::METHOD_AJAX ? $ajax_callback : null;

		$this->valid = false;

		/* TODO make compatible
		$request = Request::instance();
		$ajax_div = $request->getParam('ajax_div');
		if ($ajax_div)
			$this->addHidden('ajax_div', $ajax_div);
			*/
	}

	public function setMethod($method = self::METHOD_POST) {
		$this->method = $method;
	}

	/**
	 * no return value is passed, only the parameters are set by reference
	 *
	 * @param [in] $key string unique identifier of form field
	 *
	 * @return page number
	 */
	private function getKeyPage($key) {
		return isset($this->field_map[$key]) ? $this->field_map[$key] : null;
	}

	/**
	 * Validate a single form element
	 *
	 * @param [in] $key string
	 * @param [in] $data array all the data in the form is passed in in case we
	 * need multiple fields to consider
	 *
	 * @return boolean true if validation succeeded
	 */
	public function validateKey($key, $data) {
		$page = $this->getKeyPage($key);

		if (!isset($this->fields[$page][$key]))
			return true;

		$descriptor = $this->fields[$page][$key];

		if (!$descriptor[self::REQUIRED] && !$data[$key])
			return true;

		$value = isset($data[$key]) ? $data[$key] : '';

		if ($descriptor[self::TYPE] == self::TYPE_PASSWORD);
		else
			$this->fields[$page][$key][self::DEFAULT_VALUE] = $value;

		switch ($descriptor[self::TYPE]) {
			case self::TYPE_SELECT:
			case self::TYPE_RADIO:
			case self::TYPE_CHECK:
				if (isset($descriptor[self::VALID_VALUES]) && !isset($descriptor[self::VALID_VALUES][$value]))
					return false;
				break;

			case self::TYPE_INT:
			case self::TYPE_NUMBER:
				if (!is_numeric($value))
					return false;
				break;

			case self::TYPE_IMAGE:
				if ($value['error'] || $value['size'] == 0)
					return false;
				break;

				/*
			case self::TYPE_CAPTCHA:
				return $this->captcha()->validateCaptcha($data[$key .'_code'], $data[$key]);
				*/

			default:
				break;
		}

		if (isset($descriptor[self::VALIDATION])) {
			$v = $descriptor[self::VALIDATION];

			if (is_array($v)) {

				if (count($v) == 2 && method_exists($v[0], $v[1]))
					return call_user_func(array($v[0], $v[1]), $value);

				else {
					$return = true;

					foreach ($v as $func) {
						if (!$return)
							break;

						if (is_array($func) && method_exists($func[0], $func[1])) {
							$return = $return && call_user_func($func, $value);
							continue;
						}

						$func = $this->buildValidationCallback($func);
						if (method_exists($this, $func))
							$return = $return && $this->{$func}($value);
					}

					return $return;
				}
			}
			else if (is_string($v)) {
				$func = $this->buildValidationCallback($v);
				if (method_exists($this, $func))
					return call_user_func(array($this, $func), $value);
				else
					Log::raiseError('Bad validation function passed: '. var_export($func, true));
			}
			else
				Log::raiseError('Bad validation function passed: '. var_export($v, true));
		}

		return true;
	}

	/**
	 * validate a URL
	 *
	 * @param [in] $value string
	 *
	 * @returns boolean true if parameter is a valid URL
	 */
	public function validUrl($value) {
		return preg_match(RE_URL, $value) > 0;
	}

	/**
	 * validate an email address
	 *
	 * @param [in] $value string
	 *
	 * @returns boolean true if parameter is a valid email address
	 */
	public function validEmail($value) {
		return preg_match(RE_EMAIL, $value) > 0;
	}

	/**
	 * build a validation function callback name
	 *
	 * @param [in] $func string
	 *
	 * @returns string
	 */
	private function buildValidationCallback($func) {
		return 'valid'. strtoupper($func[0]) . substr($func, 1);
	}

	/**
	 * Validate data according to the validation types and callbacks set in the descriptor
	 *
	 * @param [in] $data if an array is passed in validate that array instead of $_REQUEST
	 * @param [in] $validate_fields list of fields to validate, defaults to all
	 *
	 * @returns boolean true if data validates properly or no data passed
	 */
	public function validate(array $data = null, array $validate_fields = null) {
		if ($data === null)
			$data = $_GET + $_POST + $_FILES;

		if (!$data) //nothing to validate never mind
			return false;

		if (is_array($validate_fields))
			$validate_fields = array_flip($validate_fields);
		else
			$validate_fields = null;

		$return = true;

		foreach ($this->required_fields as $k) {
			if (!isset($data[$k]) || !$data[$k]) {
				$page = $this->getKeyPage($k);

				if (!$validate_fields || isset($validate_fields[$k])) {
					$this->fields[$page][$k][self::ERROR] = true;
					$this->fields[$page][$k][self::ERROR_MESSAGE] = $this->getError($k);
				}

				$return = false;
			}
		}

		foreach ($data as $k => $value) {
			if (!$this->validateKey($k, $data)) {
				$page = $this->getKeyPage($k);

				if (!$validate_fields || isset($validate_fields[$k])) {
					$this->fields[$page][$k][self::ERROR] = true;
					$this->fields[$page][$k][self::ERROR_MESSAGE] = $this->getError($k);
				}

				$return = false;
			}
			//$this->descriptor[]
		}

		if ($return)
			return $data;
		else 
			return false;
	}

	/**
	 * add a descriptor for a form field, this function only handles the functional part of the form field, use setDisplay for the pretty texts
	 *
	 * @see setDisplay
	 *
	 * @param [in] $key the unique key name for posts
	 * @param [in] $type the type of form field, see the constants prefixed with TYPE_
	 * @param [in] $required
	 * @param [in] $validation validation functions to use, either string or array allowed, callbacks are allowed as well
	 * @param [in] $validation_values array of values that are allowed here
	 */
	public function addDescriptor($key, $type, $required = true, $validation = null, $validation_values = null) {
		if ($required)
			$this->required_fields[$key] = $key;

		if (!isset($this->fields[$this->current_page]))
			$this->fields[$this->current_page] = array();

		$this->field_map[$key] = $this->current_page;

		$this->fields[$this->current_page][$key] = array(
				self::TYPE => $type,
				self::REQUIRED => $required,
				self::VALIDATION => $validation,
				self::VALID_VALUES => $validation_values,
				self::ERROR => false,
				);
	}

	/**
	 * set the display related variables for the given key, must call addDescriptor first 
	 *
	 * @see addDescriptor
	 *
	 * @param [in] $key - name of form field you wish to edit
	 * @param [in] $display - form field title
	 * @param [in] $description - form field subtext
	 * @param [in] $error - form field error text
	 * @param [in] $default - default value
	 */
	public function setDisplay($key, $display, $description = '', $error = '', $default = null) {
		if ($this->getKeyPage($key) === null)
			return null;

		$page = $this->field_map[$key];
		$this->fields[$page][$key] += array(
				self::DISPLAY => $display,
				self::DESCRIPTION => $description,
				self::ERROR_MESSAGE => $error ? $error : null,
				self::DEFAULT_VALUE => $default,
				);
	}

	/**
	 * set the default value for the given key, must call addDescriptor first 
	 *
	 * @see addDescriptor
	 *
	 * @param [in] $key - name of form field you wish to edit
	 * @param [in] $default - default value
	 */
	public function setDefault($key, $default = null) {
		if ($this->getKeyPage($key) === null)
			return null;

		$page = $this->field_map[$key];
		$this->fields[$page][$key][self::DEFAULT_VALUE] = $default;
	}

	/**
	 * shorthand for adding a hidden field to a form, lots simpler than using addDescriptor
	 *
	 * @see addDescriptor
	 *
	 * @param [in] $key name of form field
	 * @param [in] $value default value of form field
	 * @param [in] $validation string or array of validation methods to use
	 * @param [in] $validation_values array of valid options
	 */
	public function addHidden($key, $value, $validation = null, $validation_values = null) {
		if (!isset($this->fields[$this->current_page]))
			$this->fields[$this->current_page] = array();

		$this->field_map[$key] = $this->current_page;
		$this->required_fields[$key] = $key;

		$this->fields[$this->current_page][$key] = array(
				self::TYPE => self::TYPE_HIDDEN,
				self::REQUIRED => true,
				self::VALIDATION => $validation,
				self::VALID_VALUES => $validation_values,
				self::DEFAULT_VALUE => $value,
				self::ERROR => false,
				);
	}

	/**
	 * shorthand for adding a submit button to a form, lots simpler than using addDescriptor
	 *
	 * @see addDescriptor
	 *
	 * @param [in] $key name of form field
	 * @param [in] $value default value of form field
	 */
	public function addSubmit($key, $value) {
		if (!isset($this->fields[$this->current_page]))
			$this->fields[$this->current_page] = array();

		$this->field_map[$key] = $this->current_page;

		$this->fields[$this->current_page][$key] = array(
			self::TYPE => self::TYPE_SUBMIT,
			self::REQUIRED => false,
			self::DEFAULT_VALUE => $value,
			self::ERROR => false,
		);
	}

	/**
	 * shorthand for adding a select box
	 *
	 * @see addDescriptor
	 *
	 * @param [in] $key string name of form field
	 * @param [in] $validation_values array of valid options
	 * @param [in] $required bool
	 */
	public function addSelect($key, $validation_values, $required = true) {
		if (!isset($this->fields[$this->current_page]))
			$this->fields[$this->current_page] = array();

		if ($required)
			$this->required_fields[$key] = $key;

		$this->field_map[$key] = $this->current_page;

		$this->fields[$this->current_page][$key] = array(
				self::TYPE => self::TYPE_SELECT,
				self::REQUIRED => $required,
				self::VALIDATION => null,
				self::VALID_VALUES => $validation_values,
				self::DEFAULT_VALUE => null,
				self::ERROR => false,
				);
	}

	/**
	 * Increment the internal page pointers, keep in mind that a new page
	 * doesn't exist until a new field descriptor is added to the page after
	 * calling this method
	 */
	public function nextPage() {
		$this->current_page++;
		$this->page_count++;
	}

	/**
	 * @param [in] $key string name of the form field we want an error message for
	 *
	 * @returns string appropriate error message for the request form key
	 */
	public function getError($key) {
		$type = null;
		$display = null;

		$page = $this->getKeyPage($key);

		if (isset($this->fields[$page][$key], $this->fields[$page][$key][self::ERROR_MESSAGE]))
			return $this->fields[$page][$key][self::ERROR_MESSAGE]; //custom error message set, return it

		else if (isset($this->fields[$page][$key])) {
			$type = $this->fields[$page][$key][self::TYPE];
			$display = $this->fields[$page][$key][self::DISPLAY];
		}
		else {
			Log::raiseError(__CLASS__ .'::'. __FUNCTION .' - Key not found in form: '. $key);
			return 'Please correct the field';
		}

		$message = '';
		switch ($type) {
			case self::TYPE_SELECT:
				$message = 'Please make a selection from the drop down box';
				break;
			case self::TYPE_RADIO:
				$message = 'Please select one of the options';
				break;
			case self::TYPE_CHECK:
				$message = 'Invalid option';
				break;
			case self::TYPE_INT:
			case self::TYPE_NUMBER:
				$message = 'Please provide a valid number';
				break;
			case self::TYPE_CHAR:
			case self::TYPE_TEXT:
			case self::TYPE_TEXTBOX:
				$message = 'Please provide a valid string';
				break;
			case self::TYPE_PASSWORD:
				$message = 'Please provide a valid password';
				break;
			case self::TYPE_CAPTCHA:
				$message = 'Please fill out the correct text as displayed in the image';
				break;
			case self::TYPE_IMAGE:
				$message = 'Please upload a valid image file';
				break;
			case self::TYPE_HIDDEN:
				$message = 'There was an error submitting the form';
				break;
			default:
				$message = 'Please correct the field';
				break;
		}

		return $message;
	}

	/**
	 * @param [in] $type - int type constant of the field
	 *
	 * @throws Exception on unknown field type
	 *
	 * @returns string form field type to be used in the View
	 */
	private function getFormType($type) {
		switch ($type) {
			case self::TYPE_SELECT:
				return 'select';
			case self::TYPE_RADIO:
				return 'radio';
			case self::TYPE_CHECK:
				return 'checkbox';
			case self::TYPE_INT:
			case self::TYPE_NUMBER:
				return 'number';
			case self::TYPE_CHAR:
			case self::TYPE_TEXT:
				return 'text';
			case self::TYPE_TEXTBOX:
				return 'textbox';
			case self::TYPE_PASSWORD:
				return 'password';
			case self::TYPE_CAPTCHA:
				return 'captcha';
			case self::TYPE_IMAGE:
				return 'image';
			case self::TYPE_HIDDEN:
				return 'hidden';
			case self::TYPE_BUTTON:
				return 'button';
			case self::TYPE_SUBMIT:
				return 'submit';
			default:
				throw new Exception(__CLASS__ .'::'. __FUNCTION__ .'@'. __LINE__ .' - type: '. $type);
		}
	}

	/**
	 * @throws Exception on unknown method
	 *
	 * @returns string form method
	 */
	private function getFormMethod() {
		switch ($this->method) {
			case self::METHOD_POST:
				return 'post';
			case self::METHOD_GET:
				return 'get';
			case self::METHOD_AJAX:
				return 'ajax';
			default:
				throw new Exception(__CLASS__ .'::'. __FUNCTION__ .' - unknown form method');
		}
	}

	/**
	 * Translate the array descriptor into DomDocument
	 *
	 * @param [in,out] $dom DomDocument
	 *
	 * @return DomElement root node of form
	 */
	public function toXML(DomDocument $dom = null) {
		if (!$dom instanceof DomDocument)
			$dom = new DomDocument('1.0', 'UTF-8');

		$form = $dom->appendChild($dom->createElement('form'));
		$form->setAttribute('method', $this->getFormMethod());
		$form->setAttribute('action', $this->action);
		$form->setAttribute('callback', $this->callback);

		$pages = count($this->fields);
		foreach ($this->fields as $page) {
			foreach ($page as $key => $element) {
				$node = $dom->createElement('element');
				$node->setAttribute('key', $key);
				$node->setAttribute('type', $this->getFormType($element[self::TYPE]));
				$node->setAttribute('default', isset($element[self::DEFAULT_VALUE]) && !is_array($element[self::DEFAULT_VALUE]) ? $element[self::DEFAULT_VALUE] : null);

				if ($element[self::TYPE] != self::TYPE_HIDDEN) {
					$node->setAttribute('required', $element[self::REQUIRED]);
					$node->setAttribute('display', isset($element[self::DISPLAY]) ? $element[self::DISPLAY] : '');
					$node->setAttribute('description', isset($element[self::DESCRIPTION]) ? $element[self::DESCRIPTION] : '');
				}
				if ($element[self::ERROR])
					$node->setAttribute('error', $element[self::ERROR_MESSAGE]);

				switch ($element[self::TYPE]) {
					case self::TYPE_RADIO:
					case self::TYPE_CHECK:
						if (is_array($element[self::VALID_VALUES])) {
							foreach ($element[self::VALID_VALUES] as $k => $v) {
								$valuenode = $dom->createElement('value');
								$valuenode->setAttribute('key', $key .'_'. $k); //TODO FIXME this needs to be post array format "bla[1] = 1", but javascript doesn't like it
								$valuenode->setAttribute('value', $k);
								$valuenode->appendChild($dom->createTextNode($v));

								if (isset($element[self::DEFAULT_VALUE]) && $element[self::DEFAULT_VALUE] == $k)
									$valuenode->setAttribute('checked', 'checked');

								$node->appendChild($valuenode);
							}
						}
						else {
							if (!$_POST || isset($_REQUEST[$key]))
								$node->setAttribute('checked', 'checked');
							$node->setAttribute('value', $element[self::DEFAULT_VALUE]);
						}
						break;

					case self::TYPE_SELECT:
						if (is_array($element[self::VALID_VALUES])) {
							foreach ($element[self::VALID_VALUES] as $k => $v) {
								$valuenode = $dom->createElement('value');
								$valuenode->setAttribute('value', $k);
								$valuenode->appendChild($dom->createTextNode($v));

								if (isset($element[self::DEFAULT_VALUE]) && $element[self::DEFAULT_VALUE] == $k)
									$valuenode->setAttribute('selected', 'selected');

								$node->appendChild($valuenode);
							}
						}
						break;

						/*
					case self::TYPE_CAPTCHA:
						$code = $this->captcha()->generateCaptcha();
						$node->setAttribute('code', $code);
						break;
						*/
				}

				$form->appendChild($node);
			}
		}

		return $form;
	}

	/*
	function captcha() {
		static $captcha = null;

		if ($captcha === null)
			$captcha = new Captcha();

		return $captcha;
	}
	*/
}

