<?php
require_once('Shaded/Framework/WoolPager.php');
require_once('Shaded/Common/sql.php');

class WoolGrid implements IteratorAggregate, Countable, ArrayAccess {
	private $name;
	private $sql;
	private $pager;
	
	private $order;
	private $dir;
	private $perPage = 50;
	private $count;
	
	private $rowSet;
	private $done = false;
	
	private $orderables = array();
	
	public function __construct($name, $sql, $params=array()) {
		$this->name = $name;
		if (is_string($sql)) {
			$this->sql = new Sql($sql, $params);
		} else {
			$this->sql = $sql;
		}
	}
	
	public function name() {
		return $this->name;
	}
	
	// Get total number of rows across all pages.
	public function totalRows() {
		if (!$this->count) {
			$this->count = WoolDb::fetchOne($this->countSql(), $this->sql->params());
		}
		
		return $this->count;
	}
	
	// IteratorAggregate interface.
	public function getIterator() {
		$this->execute();
		return $this->rowSet->getIterator();
	}
	
	// Countable interface.
	public function count() {
		$this->execute();
		return count($this->rowSet);
	}
	
	// ArrayAccess interface.
	public function offsetSet($offset, $value) {
		$this->execute();
		$this->rowSet[$offset] = $value;
	}
	public function offsetExists($offset) {
		$this->execute();
		return isset($this->rowSet[$offset]);
	}
	public function offsetUnset($offset) {
		$this->execute();
		unset($this->rowSet[$offset]);
	}
	public function offsetGet($offset) {
		$this->execute();
		return isset($this->rowSet[$offset]) ? $this->rowSet[$offset] : null;
	}
	
	// Get current page.
	public function page() {
		return $this->pager()->page();
	}
	
	public function totalPages() {
		return $this->pager()->totalPages();
	}
	
	public function setPerPage($num) {
		$this->perPage = $num;
	}
	
	public function col($html, $ref, $orderBy=null) {
		$this->orderables[$ref] = coal($orderBy, $ref);
		
		$dir = null;
		
		if ($ref == param("{$this->name}_order", $this->order)) {
			$dirParam = strtolower(param("{$this->name}_direction", $this->dir));
			$dir = (!$dirParam || $dirParam == "asc") ? "desc" : "asc";
		}
		
		$params = array(
			"{$this->name}_order" => $ref,
			"{$this->name}_direction" => $dir
		);
		
		return element_tag_build("a", array("href" => Request::uri($params)), $html);
	}
	
	public function orderBy($column, $dir=null) {
		$this->order = $column;
		$this->orderDir($dir);
	}
	
	public function orderDir($dir) {
		$this->dir = coal($dir, $this->dir);
	}
	
	private function countSql() {
		return preg_replace('/select.*?from/ius', "select count(*) from", $this->sql->toString(), 1);
	}
	
	private function orderBySql() {
		$order = param("{$this->name}_order", $this->order);
		
		if ($this->orderables) {
			$order = coal($this->orderables[$order], null);
		}
		
		if ($order) {
			$this->sql->orderBy($order, $this->getDirection());
		}
	}
	
	private function getDirection() {
		$dir = strtolower(param("{$this->name}_direction", $this->dir));
		return matchSqlOrder($dir);
	}
	
	private function limitSql() {
		$count = $this->pager()->getPerPage();
		if (!$count) { return ""; }
		
		$start = ($this->page() - 1) * $count;
		$this->sql->limit($start, $count); 
	}
	
	function filter($filterFields) {
		$filter = param("{$this->name}_filter");

		if (!$filter) {
			return;
		}
		
		$matches = array();
		// Build the filter on all the fields and insert after the WHERE.
		foreach ($filterFields as $field) {
			$matches[] = "{$field} like '%{$filter}%' ";
		}

		$insert = '(' . join(' or ', $matches) . ')';
		$this->sql->andWhere($insert);
	}
	
	private function pager() {
		if (!$this->pager) {
			$this->pager = new WoolPager($this->name, $this->totalRows(), $this->perPage);
		}
		return $this->pager;
	}
	
	private function execute($force=false) {
		if ($this->done && !$force) {
			return;
		}
		$this->done = true;
		
		// Add ordering.
		$this->orderBySql();
		
		// Add limit.
		$this->limitSql();
		
		$this->rowSet = $this->sql->rowSet();
	}
}
