<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'AbstractModel.class.php';

class ConcreteModel extends AbstractModel {
	protected $_table = 'table';

	public function __construct() {
		$this->_validator = new Validator(array(), array());

		parent::__construct();
	}

	public function doQueryUpdateTest($fields, $data, $id) {
		return $this->queryUpdate($fields, $data, $id);
	}
}

class AbstractModelTest extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		$this->model = new ConcreteModel();
	}

	public function test() {
		$this->assertTrue(true);
	}
}

?>
