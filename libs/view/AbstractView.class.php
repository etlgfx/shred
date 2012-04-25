<?php

abstract class AbstractView {

	protected $template;
	protected $ext;

	abstract public function render($template = null, array $data);
	abstract public function exists($template);

	/**
	 * Factory method to return the appropriate View class
	 *
	 * @param $class string
	 *
	 * @throw Exception if class cannot be found
	 *
	 * @returns AbstractView subclass on success
	 */
	public static function factory($class = 'twig') {
		$class = Util::toClassName($class) .'View';
		$path = PATH_LIBS .'view/'. $class .'.class.php';

		if (file_exists($path)) {
			require_once $path;

			return new $class();
		}
		else {
			throw new RuntimeException('View Class `'. $class .'` not found in: '. $path);
		}
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
	 * @param string $subdir subdirectory to look in for the template, e.g. controllername/templatefile.tpl
	 *
	 * @returns bool true if file found and successfully assigned
	 */
	public function setTemplate($template, $subdir = '') {
		//TODO make more flexible if user specified a custom extension
        if (strpos($template, $this->ext) === false)
            $template .= $this->ext;

		if ($subdir && $this->exists($subdir .'/'. $template))
			$this->template = $subdir .'/'. $template;
		else if ($this->exists($template))
			$this->template = $template;
		else
			return false;

		return true;
	}

}

?>
