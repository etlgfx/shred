<?php

namespace Shred;

interface Controller_IError {
	public function error(Exception $e, $status = 404, $message = null);
}
