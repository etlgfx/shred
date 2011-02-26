<?php

require_once dirname(__FILE__) .'/../include.php';
require_once PATH_LIBS .'ModelFilter.class.php';

class ModelFilterText extends PHPUnit_Framework_TestCase {

	public function testNoFilter() {
		$filter = new ModelFilter();

		$this->assertEquals($filter->toSQL(), '');
	}

	public function testLimit() {
		$filter = new ModelFilter();

		$this->assertInstanceOf('ModelFilter', $filter->limit(1));

		$this->assertEquals(
			trim($filter->toSQL()),
			'LIMIT 1'
		);

		$this->assertInstanceOf('ModelFilter', $filter->limit());

		$this->assertEquals(
			$filter->toSQL(),
			''
		);

		$this->assertInstanceOf('ModelFilter', $filter->limit(2, 3));

		$this->assertEquals(
			trim($filter->toSQL()),
			'LIMIT 3, 2'
		);

		$e = null;

		try {
			$filter->limit(2.3);
		}
		catch (Exception $e) { }

		$this->assertInstanceOf('Exception', $e);
	}
}

?>
