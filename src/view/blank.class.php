<?php

namespace Shred;

class View_Blank extends View_Abstract {
	public function render(array $data) {
	}

	public function exists($template) {
		return true;
	}
}
