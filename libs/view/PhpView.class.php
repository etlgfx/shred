<?php

class PhpView extends AbstractView {
	protected $loader;

	public function __construct() {
		$this->ext = '.php';
	}

	public function render($template, array $data) {

		//$template = $this->twig->loadTemplate($template);
		//echo $template->render($data);
	}

	public function exists($template) {
		//TODO not impl
		return true;
	}
}

?>
