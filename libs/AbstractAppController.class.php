<?php

require_once PATH_LIBS .'AbstractController.class.php';
require_once PATH_LIBS .'Error.class.php';
require_once PATH_LIBS .'DataContainer.class.php';

require_once PATH_LIBS .'SiteConfig.class.php';
require_once PATH_VENDORS .'smarty/Smarty.class.php';

abstract class AbstractAppController extends AbstractController {
	const DEFAULT_ACTION = 'login';

	protected $method;
	protected $url;
	protected $smarty;
	protected $data_container;

	public function __construct(URL $url) {
		$this->url = $url;
		$this->method = $this->url->getParam(0);

		if (!$this->method)
			$this->method = 'index';

		$this->config = new SiteConfig($url);
		$this->smarty = new Smarty();
		$this->data_container = new DataContainer();

		$this->smarty->template_dir = PATH_APP .'views/';
		$this->smarty->compile_dir = PATH_APP .'tmp/smarty_compile/';
		$this->smarty->cache_dir = PATH_APP .'tmp/smarty_cache/';
		$this->smarty->config_dir = PATH_APP .'config/';
		$this->smarty->plugins_dir []= PATH_APP .'vendors/smarty_plugins/';
	}

	/**
	 * not so fancy routing so AbstractController::factory can more
	 * intelligently decide what concrete controller to load
	 */
	public static function routing(URL $url) {
		return 'default';
	}

	/**
	 * Find the requested method using the current URL object and run it
	 *
	 * @throws Exception if method not defined
	 *
	 * @returns mixed, whatever the method returns
	 */
	public function execute() {
		if (method_exists($this, $this->method))
			return call_user_func_array(array($this, $this->method), $this->url->getParams(1));
		else
			throw new Exception("Undefined method requested: ". $this->method);
	}

	/**
	 * Render the page through smarty
	 *
	 * @throws Exception on error
	 */
	public function render() {
		$this->config->addStyle('base.css');
		$error = Error::inst();

		// TODO modify config to grab all variables at once
		$this->set('site', $this->config->getSite());
		$this->set('page', $this->config->getPage());
		$this->set('menu', $this->config->getMenu());
		$this->set('styles', $this->config->getStyles());

		$this->set('server_url', SERVER_URL);
		$this->set('server_gfx', SERVER_GFX);
		$this->set('server_css', SERVER_CSS);

		$this->smarty->assign($this->data_container->getVars());
		
		try {
			$this->smarty->display($this->url->getAction() .'/'. $this->method .'.tpl');
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public function error() {
		//TODO
		die('render 404 page or something: '. $this->url);
	}

	/**
	 * destroy the user session
	 */
	public function clearSession() {
		foreach ($_SESSION as $k => $v)
			unset($_SESSION[$k]);

		session_destroy();
	}

	/**
	 * redirect to the URL
	 *
	 * @param $url URL object to redirect to
	 */
	public function redirect(URL $url) {
		if ($this->url == $url)
			return;

		header('Location: '. $url);
	}

	/**
	 * sets template variables
	 */
	public function set($key, $value = null) {
		$this->data_container->set($key, $value);
	}

	/**
	 * shorthand for assigning template names
	 * TODO verify that this doesn't overwrite other values inside the template array in template vars
	 */
	public function setTemplate($area, $template) {
		$this->data_container->set('template.'. $area, $template);
	}

	/**
	 * sets template variables
	 */
	public function append($key, $value) {
		return $this->data_container->append($key, $value);
	}

	/**
	 * return whether the given key is set or not in the template vars
	 *
	 * @returns boolean
	 */
	public function exists($key) {
		return $this->data_container->is_set($key);
	}

	/**
	 * @returns string
	 */
	protected static function defaultAction() {
		return self::DEFAULT_ACTION;
	}
}

?>
