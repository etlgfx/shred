<?php

namespace Shred;

interface IShell {

	/**
	 * The default shell method to perform if none was specified
	 */
	public function _default();

	/**
	 * The Help shell uses this to display some usage information
	 *
	 * @returns array('name', 'description')
	 */
	public function description();
}

