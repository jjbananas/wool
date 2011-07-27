<?php

require_once(dirname(__FILE__) . '/../../../../../lib/simpletest/autorun.php');

class WoolFrameworkDbTests extends TestSuite {
	public function __construct() {
		parent::__construct();

		$dir = dirname(__FILE__) . "/";

		$this->addFile($dir . "TestSqlParser.php");
	}
}
