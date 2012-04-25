<?php

class PhpView extends AbstractView {
	protected $loader;

	public function __construct() {
		$this->ext = '.php';
	}

	public function render($template, array $data) {
		//TODO consider moving this to abstract view for all view classes and doing parent::render() at the beginning of these
		if (!$this->setTemplate($template)) {
			throw new NotFoundException('Unable to load template `'. $template .'`');
		}

		die($this->template);
		//$template = $this->twig->loadTemplate($template);
		//echo $template->render($data);
	}

	public function exists($template) {
		return file_exists($template);
	}
}

?>
