<?php
require_once('Wool/Framework/WoolGrid.php');

class WoolAutoGrid extends WoolGrid {
	const COL_HIDDEN = 0;
	const COL_NORMAL = 1;
	const COL_ASC = 2;
	const COL_DESC = 3;
	
	private $table;
	
	public function __construct($table) {
		$this->table = $table;
		
		parent::__construct($table, WoolTable::queryJoined($this->table));
		
		$this->cacheFilters();
		
		$this->filter(Schema::searchColumns($table));
	}
	
	private function cacheFilters() {
		if (param("{$this->table}_clear")) {
			$_SESSION['gridfilter'][$this->table] = array();
			return;
		}
		
		$filter = param("{$this->table}_filter");
		if (!is_null($filter)) {
			$_SESSION['gridfilter'][$this->table]['filter'] = $filter;
		}
	}
	
	public function isFiltering() {
		return isset($_SESSION['gridfilter'][$this->table]['filter']) && $_SESSION['gridfilter'][$this->table]['filter'];
	}
	
	public function filterParam() {
		return coal($_SESSION['gridfilter'][$this->table]['filter'], null);
	}
	
	protected function filterWildcard($filter) {
		return "{$filter}%";
	}
	
	public function orderBySql() {
		
	}
}
