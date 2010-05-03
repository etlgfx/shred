<?php

/** @class URL
 *
 * This URL object offers a simple interface for URL translation into argument
 * lists and parameter retrieval
 */
class URL {
	private $action;
	private $pagination;
	private $params;
	private $named_params;

	const K_PAGE = 'page';
	const K_COUNT = 'count';
	const K_ORDER = 'order';
	const K_DIRECTION = 'dir';

	/**
	 * constructor
	 *
	 * @param $args string URL for now, might be mixed at some point
	 *
	 * @throws Exception on bad arguments
	 */
	public function __construct($args = null) {
		if ($args) {
			if (is_string($args))
				$this->parseURL($args);
			/*
			else if (is_array($args))
				$this->arrayToURL($args);
				*/
			else
				throw new Exception("Error parsing URL: ". var_export($args));
		}
		else {
			$this->action = null;
			$this->pagination = null;
			$this->params = array();
			$this->named_params = $_GET;
		}
	}

	/**
	 * Take a fully qualified URL as input and turn it into internal URL
	 * representation with separated out parameters etc.
	 *
	 * @param $url string URL to parse
	 */
	public function parseURL($url) {
		$this->action = null;
		$this->pagination = null;
		$this->params = array();
		$this->named_params = $_GET;

		if (strpos($url, SERVER_URL) === 0)
			$url = substr($url, strlen(SERVER_URL));

		if (($p = strpos($url, '?')) !== false)
			$url = substr($url, 0, $p);

		$url = explode('/', $url);

		foreach ($url as $arg) {
			if ($arg == '')
				continue;

			if (strpos($arg, ':')) {
				$named = explode(':', $arg);

				if (count($named) == 2)
					$this->named_params[$named[0]] = $named[1];
			}
			else {
				$this->params []= $arg;
			}
		}

		$this->action = array_shift($this->params);

		if (isset($this->named_params[self::K_PAGE])) {
			$this->pagination[self::K_PAGE] = $this->named_params[self::K_PAGE];
			unset($this->named_params[self::K_PAGE]);

			if (isset($this->named_params[self::K_COUNT])) {
				$this->pagination[self::K_COUNT] = $this->named_params[self::K_COUNT];
				unset($this->named_params[self::K_COUNT]);
			}

			if (isset($this->named_params[self::K_ORDER])) {
				$this->pagination[self::K_ORDER] = $this->named_params[self::K_ORDER];
				unset($this->named_params[self::K_ORDER]);
			}

			if (isset($this->named_params[self::K_DIRECTION])) {
				$this->pagination[self::K_DIRECTION] = $this->named_params[self::K_DIRECTION];
				unset($this->named_params[self::K_DIRECTION]);
			}
		}
	}

	/**
	 * Convert the stored URL object back to string
	 *
	 * @returns string URL
	 */
	public function __toString() {
		$url = SERVER_URL;

		if (isset($this->action))
			$url .= $this->action;
		
		if (isset($this->params))
			$url .= '/'. implode('/', $this->params);

		if (isset($this->named_params)) {
			foreach ($this->named_params as $k => $v)
				$url .= '/'. $k .':'. $v;
		}

		if (isset($this->pagination))
			$url .= '/page:'. $this->pagination['page'] .'/count:'. $this->pagination['count'];

		return $url;
	}

	/**
	 * Get the requested action
	 *
	 * @returns string action name
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Set the action
	 *
	 * @param $action string
	 */
	public function setAction($action) {
		$this->action = $action;
	}

	/**
	 * Get named parameters by range, if no arguments supplied, return all parameters
	 *
	 * @param $start int offset to start array slice
	 * @param $length int number of items to return
	 *
	 * @returns array
	 */
	public function getParams($start = null, $length = null) {
		return array_slice($this->params, $start, $length);
	}

	/**
	 * Get a single parameter from either the named parameters or the numeric
	 * indices
	 *
	 * @param $index int or string
	 *
	 * @returns string value or null
	 */
	public function getParam($index) {
		if (is_numeric($index) && isset($this->params[$index]))
			return $this->params[$index];
		else if (isset($this->named_params[$index]))
			return $this->named_params[$index];
		else
			return null;
	}

	/**
	 * Get all named parameters
	 *
	 * @returns array
	 */
	public function getNamedParams() {
		return $this->named_params;
	}
}

?>
