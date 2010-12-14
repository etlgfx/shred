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

    public function testQueryUpdate() {
        $result = $this->model->doQueryUpdateTest(
            array('col1', 'col2'),
            array(
                'id' => 'skip',
                'col1' => 'val1',
                'col2' => 'val2',
            ),
            'id'
        );

        $expected = new Query('UPDATE table SET col1 = $$0, col2 = $$1 WHERE id = $$2', 'val1', 'val2', 'id');

        $this->assertEquals(
            $result->__toString(),
            $expected->__toString()
        );


        $result = $this->model->doQueryUpdateTest(
            array('company_id', 'platform_id', 'timezone_id', 'name', 'url', 'status'),
            array(
                'id' => '1',
                'url' => 'http://www.facebook.com/blackberry',
                'name' => 'asdf',
                'timezone_id' => '2',
            ),
            'id');
        $expected = new Query('UPDATE table SET url = $$0, name = $$1, timezone_id = $$2 WHERE id = $$3', 'http://www.facebook.com/blackberry', 'asdf', '2', 'id');

        $this->assertEquals(
            $result->__toString(),
            $expected->__toString()
        );
    }
}

?>
