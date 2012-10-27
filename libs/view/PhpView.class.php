<?php

class PhpView extends AbstractView {
	protected $loader;

	public function __construct() {
		$this->ext = '.php';
		$this->prefix = PATH_APP .'view/';
	}

	public function render($template, array $data) {
		//TODO consider moving this to abstract view for all view classes and doing parent::render() at the beginning of these
		if (!$this->setTemplate($template)) {
			throw new NotFoundException('Unable to load template `'. $template .'`');
		}

		extract($data);
		include $this->prefix . $this->template;
	}

	public function exists($template) {
		return file_exists($this->prefix . $template);
	}
}

?>
