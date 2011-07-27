<?php

require_once(dirname(__FILE__) . '/../../../../../lib/simpletest/autorun.php');
require_once(dirname(__FILE__) . '/../../../../../lib/Wool/Framework/Db/SqlParser.php');

class TestSqlParser extends UnitTestCase {
	private $parser;
	
	function testSimpleSelect() {
		$this->parse("select * from product");
		$this->assertSourceTable("product", null);
	}
	
	function testSimpleTableAlias() {
		$this->parse("select * from users u");
		$this->assertSourceTable("users", "u");
	}
	
	function testSimpleSelectFields() {
		$this->parse("select name, password from users u");
		$this->assertSelect("name");
		$this->assertSelect("password");
		$this->assertSourceTable("users", "u");
	}
	
	function testSingleJoin() {
		$this->parse("select name, password from users u join customers c");
		$this->assertSelect("name");
		$this->assertSelect("password");
		$this->assertSourceTable("users", "u");
		$this->assertSourceTable("customers", "c");
	}
	
	function testComplexRealWorldExample() {
		$this->parse(<<<SQL
select p.Product_ID Product_ID, p.Product_Title, p.Product_Blurb, p.Tax_Class_ID, pi.Image_Thumb, pp.Price_Base_Our
from product p
left join product_images pi on pi.Product_ID = p.Product_ID and pi.Is_Primary = 'Y'
left join product_prices pp on pp.Product_ID = p.Product_ID
left join product_prices pp2 on pp2.Product_ID = pp.Product_ID and pp.Price_Starts_On < pp2.Price_Starts_On
where p.Is_Active = 'Y' and pp2.Product_ID is null
order by p.Product_Num_Orders
limit 8
SQL
		);
		
		$this->assertSourceTable("product", "p");
		$this->assertSourceTable("product_images", "pi");
		$this->assertSourceTable("product_prices", "pp");
		$this->assertSourceTable("product_prices", "pp2");
	}

	function testSelectingCalculatedValue() {
		$this->parse(<<<SQL
select (cl.total / cl.quantity * coalesce(sum(sl.quantity), 0)) total
from cart_line cl
SQL
		);
		
		$this->assertSourceTable("cart_line", "cl");
	}

	function testMultiplyInCalculatedValue() {
		$this->parse(<<<SQL
select ((cl.total / cl.quantity) * coalesce(sum(sl.quantity), 0)) total
from cart_line cl
SQL
		);
		
		$this->assertSourceTable("cart_line", "cl");
	}
	
	
	private function parse($sql) {
		$this->parser = new SqlParser($sql);
		$this->parser->parse();
	}
	
	private function assertSelect($name, $table=null, $alias=false) {
		foreach ($this->parser->selects as $source) {
			if ($source["source"] === $name && $source["table"] === $table && $source["alias"] === $alias) {
				$this->assertTrue(true);
				return;
			}
		}
		
		$this->assertTrue(false);
	}
	
	private function assertSourceTable($table, $alias) {
		foreach ($this->parser->sourceTables as $source) {
			if ($source["source"] === $table && $source["alias"] === $alias) {
				$this->assertTrue(true);
				return;
			}
		}
		
		$this->assertTrue(false);
	}
}
