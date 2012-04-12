<?php

require_once PATH_VENDORS .'twig/lib/Twig/Autoloader.php';


class TwigView extends AbstractView {
	protected $loader;
	protected $twig;

	public function __construct() {
		Twig_Autoloader::register();

		$this->loader = new Twig_Loader_Filesystem(PATH_APP .'views/');
		$this->twig = new Twig_Environment($this->loader, array(
			'cache' => PATH_APP .'tmp/',
		));
	}

	public function render($template, array $data) {
		$template = $this->twig->loadTemplate($template);
		echo $template->render($data);
	}

	public function exists($template) {
		//TODO not impl
		return true;
	}
}

?>
