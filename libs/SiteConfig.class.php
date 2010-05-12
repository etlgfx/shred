<?php

class SiteConfig {
	private $data;
	private $page;
	private $url;

	public function __construct(URL $url) {
		$this->data = require PATH_APP .'config/__site.conf.php';

		$this->url = $url;
		$page = $url->getAction();

		if ($this->pageExists($page))
			$this->page = $page;
		else if ($page)
			throw new Exception('Page does not exist: '. $page);
		else
			$this->page = $this->data['default_page'];

		if (!isset($this->data['styles']))
			$this->data['styles'] = array();
	}

	/**
	 * Find out if the page exists
	 *
	 * @param $page string or null, if null use the internal page property
	 *
	 * @returns bool
	 */
	public function pageExists($page = null) {
		if (!$page)
			$page = $this->page;

		return isset($this->data['pages'][$page]);
	}

	/**
	 * Return site meta data configuration section
	 *
	 * @returns array
	 */
	public function getSite() {
		return $this->data['site'];
	}

	/**
	 * Return page configuration section
	 *
	 * @param $page string or null, if null use the internal page property
	 *
	 * @returns array
	 */
	public function getPage($page = null) {
		if (!$page)
			$page = $this->page;

		return $this->pageExists($page) ? $this->data['pages'][$page] : null;
	}

	/**
	 * Return the menu
	 *
	 * @param $page string or null, if null use the internal page property
	 * @returns array
	 */
	public function getMenu($page = null) {
		if (!$page)
			$page = $this->page;

		if (!isset($this->data['pages'][$page]['menu']) || !isset($this->data['menus'][$this->data['pages'][$page]['menu']]))
			return null;

		$menu = $this->data['menus'][$this->data['pages'][$page]['menu']];

		foreach ($menu as &$item) {
			$url = new URL($item);

			$action = $url->getAction();
			$config = $this->getPage($action); 

			if (!$config)
				throw new Exception("Configuration error: $action doesn't exist in page list");

			$item = $this->getPage($action) + array(
				'url' => $url->__toString(),
			);

			unset($item['menu']);

			if ($action == $page)
				$item['selected'] = true;
		}

		return $menu;
	}

	/**
	 * return whether the requested page requires login
	 *
	 * @param $page string or null, if null use the internal page property
	 *
	 * @returns bool
	 */
	public function requireLogin($page = null) {
		if (!$page)
			$page = $this->page;

		return $this->pageExists($page) && (!isset($this->data['pages'][$page]['require_login'])
			|| $this->data['pages'][$page]['require_login']);
	}


	/**
	 * return whether the request should be treated as an ajax request
	 *
	 * @param $page string or null, if null use the internal page property
	 *
	 * @returns bool
	 */
	public function isAjax($page = null) {
		if (!$page)
			$page = $this->page;

		return $this->pageExists($page) && isset($this->data['pages'][$page]['ajax']);
	}


	/**
	 * this method allows for easy variable substitution and caching of
	 * variables in css files
	 *
	 * @param $stylesheet string, relative path to the css file. relative to the PATH_CSS constant
	 *
	 * @returns boolean true on success
	 *
	 * @throws Exception on failure
	 */
	public function addStyle($stylesheet) {
		$path = PATH_CSS . $stylesheet;

		if (file_exists($path)) {

			$url = substr(SERVER_URL, 3 + strpos(SERVER_URL, '://'));
			$url = str_replace(array('/', ':', ';', '-'), '_', $url);
			$url = trim($url, '_');

			$cache = $url .'.'. str_replace(array('/', ':', ';', '-'), '_', $stylesheet);
			$cache_path = PATH_CSS . SUFFIX_CACHE . $cache;

			if (!file_exists($cache_path) || filemtime($path) > filemtime($cache_path)) {
				ob_start();

				require_once $path;

				$css = ob_get_contents();

				ob_end_clean();

				if (file_put_contents($cache_path, $css) === false)
					throw new Exception("Could not write CSS cache file to: ". PATH_CSS . SUFFIX_CACHE);
			}

			$this->data['styles'] []= SERVER_CSS . SUFFIX_CACHE . $cache;
		}
		else
			return false;

		return true;
	}

	/**
	 */
	public function getStyles() {
		return $this->data['styles'];
	}

	public function getConfigData() {
		return array(
			'site' => $this->getSite(),
			'page' => $this->getPage(),
			'menu' => $this->getMenu(),
			'styles' => $this->getStyles(),
		);

	}

}

?>
