<?php

abstract class AbstractAppController extends AbstractController {

	protected $smarty;
	protected $ajax;


	/**
	 * Setup the current controller instance with all the defaults. SiteConfig,
	 * DataContainer, Smarty
	 *
	 * @param Request $request
	 */
	public function __construct(Request $request) {
		parent::__construct($request);

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
	 * Render the page through smarty
	 *
	 * @throws Exception on error
	 */
	public function render() {
		$this->setupAppData();
		
		$template = $this->getTemplate();

		if ($this->isAjax()) {
			header('Content-type: application/json');

			try {
				echo json_encode(array(
							'content' => $this->smarty->fetch($template),
							'_messages' => Log::inst()->getUserErrorsArray(),
							'_siteconfig' => array(
								'site' => $this->config->getSite(),
								'page' => $this->config->getPage(),
							),
				));
			}
			catch (Exception $e) {
				Log::raise($e->getMessage(), Log::APP_ERROR);

				echo json_encode(array(
							'_messages' => Log::inst()->getUserErrorsArray(),
							'_siteconfig' => array(
								'site' => $this->config->getSite(),
								'page' => $this->config->getPage(),
							),
				));
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
	 * @param int $state Dispatcher state, auth / exec / init
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
	 * Assign a new template to the current request, this method ensures you're
	 * using an existing template file or not.
	 *
	 * You can pass a full relative path to the template file with or without
	 * .tpl extension; or you can pass the name of a template within the current
	 * action (e.g. current action is users, pass in 'index', to request the
	 * template 'users/index.tpl')
	 *
	 * @param string $template template name
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
			$template = $this->request->getController() .'/'. $this->request->getAction() .'.tpl';

		return $template;
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
	 * @returns boolean
	 */
	protected function isAjax() {
		return $this->ajax;
	}

}

?>