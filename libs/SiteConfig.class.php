<?php

require_once PATH_LIBS .'URL.class.php';
require_once PATH_LIBS .'Request.class.php';

/**
 * @class SiteConfig stores top level site configuration options, like which
 * pages are available, which style sheets to include, which scripts to include.
 *
 * Currently it is not very fine grained, only going down the the controller
 * level, finer grained control is available by implementing checks in
 * individual controller methods.
 */
class SiteConfig {
	private $data;
	private $page;

	/**
	 * @param Request $request this is the current request, currently we only use this
	 * to get the controller name i.e. page name used to server the page
	 * @param array $config this is the site config
	 */
	public function __construct(Request $request, array $config = null) {
		$this->data = $config;

		$page = $request->getController();

		if ($this->pageExists($page)) {
			$this->page = $page;
		}

		if (!isset($this->data['styles'])) {
			$this->data['styles'] = array();
		}
		else if (is_array($this->data['styles'])) {
			$s = $this->data['styles'];
			$this->data['styles'] = array();

			foreach ($s as $style) {
				$this->addStyle($style);
			}
		}

		if (!isset($this->data['scripts'])) {
			$this->data['scripts'] = array();
		}
	}


	/**
	 * Find out if the page exists
	 *
	 * @param string|null $page if null use the internal page property
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
	 * @param string|null $page if null use the internal page property
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
	 * @param string|null $page if null use the internal page property
	 *
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
	 * @param string|null $page if null use the internal page property
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
	 * @param string|null $page if null use the internal page property
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
	 * @param string $stylesheet relative path to the css file. relative to the PATH_CSS constant
	 *
	 * @throws Exception on failure
	 *
	 * @returns boolean true on success
	 */
	public function addStyle($stylesheet) {
		$path = PATH_CSS . $stylesheet;

		if (isset($this->data['styles'][$stylesheet])) {
			return true;
		}
		if (file_exists($path)) {

			$url = substr(SERVER_URL, 3 + strpos(SERVER_URL, '://'));
			$url = str_replace(array('/', ':', ';', '-'), '_', $url);
			$url = trim($url, '_');

			$cache = $url .'.'. str_replace(array('/', ':', ';', '-'), '_', $stylesheet);
			$cache_path = PATH_CSS . SUFFIX_CACHE . $cache;

			if (!file_exists($cache_path) || filemtime($path) > filemtime($cache_path)) {
				ob_start();

				require_once $path;

				$contents = ob_get_contents();

				ob_end_clean();

				if (file_put_contents($cache_path, $contents) === false) {
					throw new Exception("Could not write CSS cache file to: ". PATH_CSS . SUFFIX_CACHE);
				}
			}

			$this->data['styles'][$stylesheet] = SERVER_CSS . SUFFIX_CACHE . $cache;
		}
		else {
			return false;
		}

		return true;
	}


	/**
	 * @returns array of css files locations
	 */
	public function getStyles() {
		return $this->data['styles'];
	}


	/**
	 * TODO refactor this with addStyle() because there's a lot of duplicate
	 * code here
	 */
	public function addScript($script, $interpret = false) {
		$path = PATH_JS . $script;

		if (isset($this->data['scripts'][$script])) {
			return true;
		}
		else if (file_exists($path)) {

			if ($interpret === true) {
				$url = substr(SERVER_URL, 3 + strpos(SERVER_URL, '://'));
				$url = str_replace(array('/', ':', ';', '-'), '_', $url);
				$url = trim($url, '_');

				$cache = $url .'.'. str_replace(array('/', ':', ';', '-'), '_', $script);
				$cache_path = PATH_JS . SUFFIX_CACHE . $cache;

				if (!file_exists($cache_path) || filemtime($path) > filemtime($cache_path)) {
					ob_start();

					require_once $path;

					$contents = ob_get_contents();

					ob_end_clean();

					if (file_put_contents($cache_path, $contents) === false) {
						throw new Exception("Could not write JS cache file to: ". PATH_JS . SUFFIX_CACHE);
					}
				}

				$this->data['scripts'][$script] = SERVER_JS . SUFFIX_CACHE . $cache;
			}
			else {
				$this->data['scripts'][$script] = SERVER_JS . $script;
			}
		}
		else {
			return false;
		}

		return true;
	}


	public function getScripts() {
		return $this->data['scripts'];
	}


	/**
	 * return an array of all config data required to render the page
	 *
	 * @returns array
	 */
	public function getConfigData() {
		return array(
			'site' => $this->data['site'],
			'page' => $this->getPage(),
			'menu' => $this->getMenu(),
			'styles' => $this->data['styles'],
			'scripts' => $this->data['scripts'],
		);

	}

}

?>
