<?php

require_once PATH_VENDORS .'smarty/Smarty.class.php';

class SmartyView extends AbstractView {
	protected $smarty;

	public function __construct() {
		$this->smarty = new Smarty();
		$this->smarty->setTemplateDir(PATH_APP .'views/');
		$this->smarty->setCompileDir(PATH_APP .'tmp/smarty_compile/');
		$this->smarty->setCacheDir(PATH_APP .'tmp/smarty_cache/');
		$this->smarty->setConfigDir(PATH_APP .'config/');
		$this->smarty->setPluginsDir(PATH_APP .'vendors/smarty_plugins/');
	}

	public function render($template, array $data) {
		$this->smarty->assign($data);
		$this->smarty->display($template);
	}

	public function exists($template) {
		return $this->smarty->templateExists($template);
	}
}

?>
