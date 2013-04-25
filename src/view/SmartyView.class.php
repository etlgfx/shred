<?php

class SmartyView extends AbstractView {
	protected $smarty;

	public function __construct() {
		$this->smarty = new Smarty();
		$this->smarty->setTemplateDir(PATH_APP .'view/');
		$this->smarty->setCompileDir(PATH_APP .'tmp/smarty_compile/');
		$this->smarty->setCacheDir(PATH_APP .'tmp/smarty_cache/');
		$this->smarty->setConfigDir(PATH_APP .'config/');
		$this->smarty->setPluginsDir(PATH_APP .'vendors/smarty_plugins/');

		$this->ext = '.tpl';
	}

	public function render(array $data) {
		$this->smarty->assign($data);
		return $this->smarty->fetch($template);
	}

	public function exists($template) {
		return $this->smarty->templateExists($template);
	}
}

?>
