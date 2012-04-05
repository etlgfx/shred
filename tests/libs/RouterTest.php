<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'Router.class.php';

class RouterTest extends PHPUnit_Framework_TestCase {

	public function setup() {
		//$this->config
	}

	public function testDefaultRoute() {
		$router = new Router();
		$request = $router->route();

		$this->assertInstanceOf('Request', $request);
		$this->assertEquals('default', $request->getController());
		$this->assertEquals('index', $request->getAction());
		$this->assertNull($request->getParam(0));

		$request = $router->route(Router::DEFAULT_REQUEST_METHOD, 'notdefault/custom');

		$this->assertInstanceOf('Request', $request);
		$this->assertEquals('notdefault', $request->getController());
		$this->assertEquals('custom', $request->getAction());
		$this->assertNull($request->getParam(0));

	}

	/**
	 * @expectedException RedirectException
	 */
	public function testDefaultRouteRedirect() {
		Config::set('router.default', 'home');

		$router = new Router();

		$request = $router->route(Router::DEFAULT_REQUEST_METHOD, '');
	}
}

?>
