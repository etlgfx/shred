<?php

require_once PATH_LIBS .'Util.class.php';
require_once PATH_LIBS .'ShellParams.class.php';

abstract class Shell {
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
			call_user_func_array(array($this, '_default'), $this->params->getArguments());
		else if (method_exists($this, $method))
			call_user_func_array(array($this, $method), $this->params->getArguments());
		else
			throw new Exception('Unable to execute, method does not exist: '. $method);
	}

	public static function factory($shell_name) {
		$class = Util::toClassName($shell_name);
		
		if (file_exists(PATH_CODE .'shell/'. $class .'.class.php'))
			require_once PATH_CODE .'shell/'. $class .'.class.php';
		else if (file_exists(PATH_LIBS .'shell/'. $class .'.class.php'))
			require_once PATH_LIBS .'shell/'. $class .'.class.php';

		if (class_exists($class))
			return new $class();
		else
			throw new Exception("Shell Class not found: ". $class);
	}

    /**
     * The default shell method to perform if none was specified
     */
	abstract public function _default();

    /**
     * The Help shell uses this to display some usage information
     *
     * @returns array('name', 'description')
     */
    abstract public function description();
}

?>
