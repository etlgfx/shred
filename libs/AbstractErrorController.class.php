<?php

abstract class AbstractErrorController extends AbstractController {
    abstract public function error($status, $message);
}

?>
