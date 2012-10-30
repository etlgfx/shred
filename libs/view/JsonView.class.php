<?php

class JsonView extends AbstractView {

	protected $mimetype = 'application/json';

	/**
	 * Implementing the abstract render method from 
	 * AbstractView. This method will output a JSON string and 
	 * output the corresponding JSON header
	 */
	public function render(array $data) {
		return json_encode($data);
	}

	/**
	 * Implementing the abstract exists method from AbstractView
	 */
	public function exists($template) {
		return true;
	}

}
