<?php

class BlankView extends AbstractView {
	public function render(array $data) {
	}

	public function exists($template) {
		return true;
	}
}
