<?php

namespace Shred;

abstract class AbstractShell implements IShell {
	protected $required = array();
	protected $switches = array();
	protected $params;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->params = new ShellParams($this->required, $this->switches);
	}

	/**
	 * Execute the requested method
	 *
	 * @throws Exception if method not found
	 */
	public function execute() {
		$method = Util::toMethodName($this->params->getArgument(1));

		if (!$method)
			call_user_func_array(array($this, '_default'), $this->params->getArguments(2));
		else if (method_exists($this, $method))
			call_user_func_array(array($this, $method), $this->params->getArguments(2));
		else
			throw new Exception('Unable to execute, method does not exist: '. $method);
	}

	public static function factory($shell_name) {
		$class = Util::toClassName($shell_name) .'Shell';

		return new $class();
	}
}

