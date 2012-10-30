<?php

interface IErrorController {
	public function error(Exception $e, $status = 404, $message = null);
}
