<?php

namespace Shred;

class View_Json extends View_Abstract {

	protected $mimetype = 'application/json';

	/**
	 * Implementing the abstract render method from 
	 * AbstractView. This method will output a JSON string and 
	 * output the corresponding JSON header
	 */
	public function render(array $data) {
		return json_encode(array('meta' => array(), 'data' => $data));
	}

	/**
	 * Implementing the abstract exists method from AbstractView
	 */
	public function exists($template) {
		return true;
	}

}
