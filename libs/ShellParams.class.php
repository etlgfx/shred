<?php

class ShellParams {
	protected $options = array();
	protected $args = array();

	public function __construct(array $required = array(), array $switches = array()) {
		$this->parseParams($required, $switches);
	}

	/**
	 * Get all arguments that aren't the class and action name
	 *
	 * @returns array
	 */
	public function getArguments() {
		return array_slice($this->args, 2);
	}

	/**
	 * Return a command line argument passed by index, this skips option flags
	 * etc.
	 *
	 * @param $index int
	 *
	 * @returns mixed, string usually, null if nothing found
	 */
	public function getArgument($index) {
		if (!is_int($index))
			throw new Exception('Invalid parameter index: '. $index);

		return isset($this->args[$index]) ? $this->args[$index] : null;
	}

	/**
	 * Return the value of the option requested
	 *
	 * @param $option mixed. should be string. name of the option to retrieve
	 *
	 * @throws Exception on invalid parameter, or strange values
	 *
	 * @returns mixed, string usually
	 */
	public function getOption($option) {
		if (!$option)
			throw new Exception('Invalid parameter option: '. $option);

		return isset($this->options[$option]) ? $this->options[$option] : null;
	}

	/**
	 * Return the value of the option request as a boolean. For example if you
	 * have something like -o true, 'true' will be returned as a boolean value,
	 * so you could also have specified 'on', '1' or similar instead.
	 *
	 * @param $option mixed. should be string. name of the option to retrieve
	 *
	 * @throws Exception on invalid parameter, or strange values
	 *
	 * @returns boolean on success, null on error
	 */
	public function getOptionBoolean($option) {
		if (!$option)
			throw new Exception('Invalid parameter option: '. $option);

		if (!isset($this->options[$option]))
			return null;

		$value = $this->getOption($option);

		if (is_bool($value))
			return $value;
		else if (is_numeric($value))
			return $value && true;
		else if (is_string($value)) {
			switch (strtolower($value)) {
				case 'true':
				case 't':
				case 'on':
				case 'yes':
				//case 'enable':
				//case 'with':
					return true;
				case 'false':
				case 'f':
				case 'off':
				case 'no':
				//case 'disable':
				//case 'without':
					return false;
				default:
					return null;
			}
		}
		else
			throw new Exception('This is a command line argument, other types are basically undefined behavior');

		//NEVER REACHED
		return null;
	}


	/**
	 * Parses command line parameters $argv and $argc
	 *
	 * @param $required - a list of required parameters
	 * @param $switches - a list of parameters that are switches (i.e. do not
	 * accept a second parameter `-v` vs `-f filename`)
	 *
	 * @throws Exception on badly initialized argv and argc, or missing required param
	 */
	private function parseParams(array $required = array(), array $switches = array()) {
		global $argv, $argc;

		if (!isset($argv, $argc))
			throw new Exception('Badly initialized parameters');

		$switches = array_flip($switches);

		reset($argv);

		while ($value = next($argv)) {
			if ($value[0] == '-') {
				if ($value[1] == '-') {
					if (strpos($value, '=')) {
						list($key, $value) = explode('=', substr($value, 2));
						$this->options[$key] = $value;
						continue;
					}
					else {
						$value = substr($value, 2);
						if (strpos($value, '-')) {
							list($switch, $key) = explode('-', $value, 2);

							switch (strtolower($switch)) {
								case 'with':
								case 'enable':
									$this->options[$key] = true;
									continue 2;
								case 'without':
								case 'disable':
									$this->options[$key] = false;
									continue 2;
							}
						}

						$this->options[$value] = true;
						continue;
					}
				}

				$value = substr($value, 1);

				if (isset($switches[$value]))
					$this->options[$value] = true;
				else
					$this->options[$value] = next($argv);
			}
			else
				$this->args []= $value;
		}

		foreach ($required as $req)
			if (!isset($this->options[$req]))
				throw new Exception('Required parameter: '. $req .'; was not set');
	}

}

?>
