<?php

require_once(dirname(__FILE__) . '/../lib/simpletest/autorun.php');

class SanityCheck extends UnitTestCase {
	public function testSanityCheck() {
		$this->assertTrue(true);
	}
}
