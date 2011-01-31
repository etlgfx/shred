<?php

require_once PATH_LIBS .'AbstractController.class.php';
require_once PATH_LIBS .'Log.class.php';
require_once PATH_LIBS .'DataContainer.class.php';
require_once PATH_LIBS .'SiteConfig.class.php';

require_once PATH_VENDORS .'smarty/Smarty.class.php';

abstract class AbstractAppController extends AbstractController {
	protected $request;
	protected $smarty;
	protected $data_container;
	protected $ajax;


    /**
     * Setup the current controller instance with all the defaults. SiteConfig,
     * DataContainer, Smarty
     *
     * @param $request
     */
	public function __construct(Request $request) {
        parent::__construct($request);

		$this->method = $this->request->getAction();


		if (!$this->method) {
			$this->method = 'index';
        }

		$this->config = new SiteConfig($request, Config::get('site_config'));
		$this->data_container = new DataContainer();

        //TODO does this distinction make sense? we can still use template on an
        //ajax request !?!?
		if (!$this->config->isAjax()) {
			$this->ajax = false;
			$this->smarty = new Smarty();
			$this->smarty->template_dir = PATH_APP .'views/';
			$this->smarty->compile_dir = PATH_APP .'tmp/smarty_compile/';
			$this->smarty->cache_dir = PATH_APP .'tmp/smarty_cache/';
			$this->smarty->config_dir = PATH_APP .'config/';
			$this->smarty->plugins_dir []= PATH_APP .'vendors/smarty_plugins/';
		}
		else {
			$this->ajax = true;
        }
	}


	/**
	 * Find the requested method using the current URL object and run it
	 *
	 * @throws Exception if method not defined
	 *
	 * @returns mixed, whatever the method returns
	 */
	public function execute() {
		if (method_exists($this, $this->method)) {
			return call_user_func_array(array($this, $this->method), $this->request->getParams());
        }
		else {
			throw new Exception("Undefined method requested: ". $this->method);
        }
	}


	/**
	 * Render the page through smarty
	 *
	 * @throws Exception on error
	 */
	public function render() {
		$this->setupAppData();
		
		$template = $this->getTemplate();

		if ($this->isAjax()) {
			header('Content-type: text/javascript');

			try {
				die(json_encode(array(
							'content' => $this->smarty->fetch($template),
							'_messages' => Log::inst()->getUserErrorsArray(),
							'_siteconfig' => array(
								'site' => $this->config->getSite(),
								'page' => $this->config->getPage(),
							),
				)));
			}
			catch (Exception $e) {
				Log::raise($e->getMessage(), Log::APP_ERROR);

				die(json_encode(array(
							'_messages' => Log::inst()->getUserErrorsArray(),
							'_siteconfig' => array(
								'site' => $this->config->getSite(),
								'page' => $this->config->getPage(),
							),
				)));
			}
		}
		else {
			try {
				$this->smarty->display($template);
			}
			catch (Exception $e) {
				echo $e->getMessage();
			}
		}
	}


	/**
	 * Render an error page if possible, if not just spit out a plain text error
	 *
	 * @param $state int - Dispatcher state, auth / exec / init
	 *
	 * @see Dispatcher
	 */
	public function error($state) {
		$template = 'generic';

		switch ($state) {
			case Dispatcher::STATE_AUTH:
				header('HTTP/1.1 403 Forbidden');
				$this->redirect(new URL(Config::get('router.default'), array('request_uri' => $this->request->getUrl()->__toString())), 1);
				$template = '403';
				break;

			case Dispatcher::STATE_EXEC:
			case Dispatcher::STATE_INIT:
				header('HTTP/1.1 404 Not Found');
				$template = '404';
				break;
		}

		$this->setupAppData();

		try {
			$this->smarty->display('_error/'. $template .'.tpl');
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}


	/**
	 * Retrieve all necessary data from site config, errors, and data container
	 * and send them to smarty
	 */
	protected function setupAppData() {
		$this->smarty->assign($this->data_container->getVars());

		$this->smarty->assign('_siteconfig', $this->config->getConfigData());
		$this->smarty->assign('_messages', Log::inst()->getUserErrorsArray());

		$this->smarty->assign('request_uri', REQUEST_URI);
		$this->smarty->assign('server_url', SERVER_URL);
		$this->smarty->assign('server_gfx', SERVER_GFX);
		$this->smarty->assign('server_css', SERVER_CSS);
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
	 * Redirect to the passed URL, if the current request is in AJAX mode the
	 * Location header will TODO ????
	 *
	 * @param $url URL object to redirect to
	 * @param $timeout int - number of seconds to wait, this will cause the
	 *     header to be a refresh header, instead of a location one
	 */
	public function redirect(URL $url, $timeout = null) {
		if ($this->request->getUrl() == $url) //TODO hacky, consider changing the interface to the current URL?
			return;

		if (is_int($timeout))
			header('Refresh: '. $timeout .'; url='. $url);
		else
			header('Location: '. $url);
	}


	/**
	 * sets template variables
	 *
	 * @param $key string name of template variable to set
	 * @param $value mixed
	 *
	 * @see DataContainer
	 */
	public function set($key, $value = null) {
		$this->data_container->set($key, $value);
	}


	/**
	 * Assign a new template to the current request, this method ensures you're
	 * using an existing template file or not.
	 *
	 * You can pass a full relative path to the template file with or without
	 * .tpl extension; or you can pass the name of a template within the current
	 * action (e.g. current action is users, pass in 'index', to request the
	 * template 'users/index.tpl')
	 *
	 * @param $template string template name
	 *
	 * @returns bool true if file found and successfully assigned
	 */
	public function setTemplate($template) {
		if (file_exists($this->smarty->template_dir . $template))
			$this->data_container->set('template', $template);
		else if (file_exists($this->smarty->template_dir . $template .'.tpl'))
			$this->data_container->set('template', $template .'.tpl');
		else if (file_exists($this->smarty->template_dir . $this->request->getController() .'/'. $template .'.tpl'))
			$this->data_container->set('template',  $this->request->getController() .'/'. $template .'.tpl');
		else
			return false;

		return true;
	}


	/**
	 * get the current page's template
	 *
	 * @returns string - relative path to template directory
	 */
	public function getTemplate() {
		$template = $this->data_container->get('template');

		if (!$template)
			$template = $this->request->getController() .'/'. $this->method .'.tpl';

		return $template;
	}


	/**
	 * sets template variables
	 *
	 * @param $key string - name of variable
	 * @param $value mixed
	 *
	 * @see DataContainer.append()
	 *
	 * @returns void
	 */
	public function append($key, $value) {
		return $this->data_container->append($key, $value);
	}


	/**
	 * return whether the given key is set or not in the template vars
	 *
	 * @param $key string - name of variable to check
	 *
	 * @returns boolean
	 */
	public function exists($key) {
		return $this->data_container->is_set($key);
	}


	/**
	 * Set the current request into AJAX mode
	 */
	protected function setAjax() {
		$this->ajax = true;
	}


	/**
	 * Return whether the current request should be handled / rendered as an
	 * AJAX request (JSON)
	 *
	 * @retuns boolean
	 */
	protected function isAjax() {
		return $this->ajax;
	}

}

?>
