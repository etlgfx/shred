<?php

class Request {
	protected $controller;
	protected $action;
	protected $params;
	protected $headers;
	protected $method;
	protected $url;

	public function __construct($method, $controller = null, $action = null, array $params = null) {
		$this->setMethod($method);

		if ($controller) {
			$this->setController($controller);
		}
		if ($action) {
			$this->setAction($action);
		}

		$this->params = $params ? $params : array();

		$this->url = new URL('' /*REQUEST_URI*/);
	}

	/**
	 * Set HTTP Request Method. This can be one of POST, GET, DELETE, PUT,
	 * PATCH, etc.
	 *
	 * @param string $method
	 *
	 * @throws Exception
	 */
	public function setMethod($method) {
		self::validRequestMethod($method);

		$this->method = $method;
	}

	/**
	 * Set Controller name property.
	 *
	 * @param string $controller
	 *
	 * @throws Exception
	 */
	public function setController($controller) {
		if (is_string($controller) && $controller) {
			$this->controller = $controller;
		}
		else {
			throw new InvalidArgumentException('Invalid parameter: '. $controller);
		}
	}

	/**
	 * Set Action property. This is the Controller method that will be called.
	 *
	 * @param string $action
	 *
	 * @throws Exception
	 */
	public function setAction($action) {
		if (is_string($action) && $action) {
			$this->action = $action;
		}
		else {
			throw new InvalidArgumentException('Invalid parameter: '. $action);
		}
	}

	/**
	 * Append a parameter to the params array
	 *
	 * @param mixed $param
	 */
	public function addParam($param) {
		if ($param) {
			$this->params []= $param;
		}
	}

	/**
	 * @param mixed $index
	 *
	 * @return mixed
	 */
	public function getParam($index) {
		return isset($this->params[$index]) ? $this->params[$index] : null;
	}

	/**
	 * @returns array
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @return string
	 */
	public function getController() {
		return $this->controller;
	}

	/**
	 * @returns string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @returns URL
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @returns string
	 */
	public function __toString() {
		return $this->url->__toString();
	}

	/**
	 * @param string $method
	 *
	 * @return string if valid
	 * @throw InvalidArgumentException if invalid
	 */
	public static function validRequestMethod($method) {
		$method = strtolower($method);

		switch ($method) {
			case 'get': case 'post': case 'put': case 'delete':
			case 'patch': case 'upgrade': case 'options':
				return $method;

			default:
				throw new InvalidArgumentException('Invalid parameter: '. $method);
		}
	}
}

?>
