<?php

namespace Shred;

class View_Twig extends View_Abstract {
	protected $loader;
	protected $twig;
	protected $prefix;

	public function __construct() {
		\Twig_Autoloader::register();

		$this->prefix = PATH_APP .'view/';
		$this->loader = new \Twig_Loader_Filesystem($this->prefix);
		$this->twig = new \Twig_Environment($this->loader, array(
			'cache' => PATH_APP .'tmp',
			'auto_reload' => true,
		));

		$this->ext = '.twig';
	}

	public function render(array $data) {
		$template = $this->twig->loadTemplate($this->template);
		return $template->render($data);
	}

	public function exists($template) {
		return file_exists($this->prefix . $template);
	}
}

?>
