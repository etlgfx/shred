<?php

class TwigView extends AbstractView {
	protected $loader;
	protected $twig;

	public function __construct() {
		Twig_Autoloader::register();

		$this->loader = new Twig_Loader_Filesystem(PATH_APP .'views/');
		$this->twig = new Twig_Environment($this->loader, array(
			'cache' => PATH_APP .'tmp/',
			'auto_reload' => true,
		));

		$this->ext = '.twig';
	}

	public function render($template, array $data) {
		$template = $this->twig->loadTemplate($template . $this->ext);
		return $template->render($data);
	}

	public function exists($template) {
		//TODO not impl
		return true;
	}
}

?>
