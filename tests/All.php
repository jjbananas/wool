<?php

require_once(dirname(__FILE__) . '/../lib/simpletest/autorun.php');

class AllTests extends TestSuite {
	public function __construct() {
		parent::__construct();

		$dir = dirname(__FILE__) . "/";

		// Files
		$this->addFile($dir . "SanityCheck.php");

		// Other suites
		$this->addFile($dir . "lib/Wool/Framework/Db/TestSqlParser.php");
	}
}
