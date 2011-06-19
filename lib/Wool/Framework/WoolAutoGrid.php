<?php
require_once('Wool/Framework/WoolGrid.php');

class WoolAutoGrid extends WoolGrid {
	const COL_HIDDEN = 0;
	const COL_NORMAL = 1;
	const COL_ASC = 2;
	const COL_DESC = 3;
	
	private $table;
	private $allColumns;
	private $visibleColumns;
	private $sortColumns;
	private $columnNames;
	
	public function __construct($table, $where=null) {
		$this->table = $table;
		$this->allColumns = Schema::summaryColumns($this->table);

		parent::__construct($table, WoolTable::queryJoined($this->table, $this->allColumns, $where));
		
		$this->cacheColumns();
		$this->cacheFilters();
		
		$this->filter(Schema::searchColumns($table));
	}
	
	private function cacheColumns() {
		$cols = isset($_SESSION['grids'][$this->table]['cols']) ? $_SESSION['grids'][$this->table]['cols'] : array();
		$this->visibleColumns = array();
		$this->sortColumns = array();
		
		// Add new columns.
		foreach ($this->allColumns as $column) {
			if (!isset($cols[$column])) {
				$cols[$column] = self::COL_NORMAL;
			}
			
			$this->columnNames[$column] = Schema::columnName($this->table, $column);
		}
		
		// Cache column info.
		foreach ($cols as $column=>$state) {
			// Clear invalid columns
			if (!in_array($column, $this->allColumns)) {
				unset($cols[$column]);
				continue;
			}
			
			if ($state != self::COL_HIDDEN) {
				$this->visibleColumns[$column] = $state;
			}
			
			if ($state == self::COL_ASC || $state == self::COL_DESC) {
				$this->sortColumns[$column] = $state;
			}
		}
		
		$_SESSION['grids'][$this->table]['cols'] = $cols;
	}
	
	// Get names of all columns, including hidden columns.
	public function allColumns() {
		return $this->allColumns;
	}
	
	public function visibleColumns() {
		return $this->visibleColumns;
	}
	
	public function columnVisible($column) {
		return isset($this->visibleColumns[$column]);
	}
	
	public function setVisibleColumns($columns) {
		$cols = isset($_SESSION['grids'][$this->table]['cols']) ? $_SESSION['grids'][$this->table]['cols'] : array();
		
		foreach ($cols as $column=>$state) {
			if (!in_array($column, $columns)) {
				$cols[$column] = self::COL_HIDDEN;
				unset($this->visibleColumns[$column]);
				unset($this->sortColumns[$column]);
			}
			else if ($state == self::COL_HIDDEN) {
				$cols[$column] = self::COL_NORMAL;
				$this->visibleColumns[$column] = self::COL_NORMAL;
			}
		}
		
		$_SESSION['grids'][$this->table]['cols'] = $cols;
	}
	
	// Column:Name keys for all columns.
	public function columnOptions() {
		return $this->columnNames;
	}
	
	public function sortColumns() {
		return $this->sortColumns;
	}
	
	public function setColumnSorts($sorts) {
		$cols = isset($_SESSION['grids'][$this->table]['cols']) ? $_SESSION['grids'][$this->table]['cols'] : array();
		
		foreach ($cols as $column=>$state) {
			if (isset($sorts[$column])) {
				$cols[$column] = $sorts[$column];
				$this->sortColumns[$column] = $sorts[$column];
			}
			else if ($state != self::COL_HIDDEN) {
				$cols[$column] = self::COL_NORMAL;
				unset($this->sortColumns[$column]);
			}
		}
		
		$_SESSION['grids'][$this->table]['cols'] = $cols;
	}
	
	public function setColumnPositions($columns) {
		$cols = isset($_SESSION['grids'][$this->table]['cols']) ? $_SESSION['grids'][$this->table]['cols'] : array();
		
		$new = array();
		
		foreach ($columns as $column) {
			$new[$col] = isset($grid[$col]) ? $grid[$col] : self::COL_NORMAL;
		}
		
		foreach (Schema::columns($table) as $col) {
			if (!isset($new[$col])) {
				$new[$col] = isset($grid[$col]) ? $grid[$col] : self::COL_NORMAL;
			}
		}
		
		$_SESSION['grids'][$this->table]['cols'] = $cols;
	}
	
	private function cacheFilters() {
		if (param("{$this->table}_clear")) {
			$_SESSION['grids'][$this->table]['filter'] = array();
			return;
		}
		
		$load = param("{$this->table}_load");
		if ($load && isset($_SESSION['grids'][$this->table]['filters'][$load])) {
			$_SESSION['grids'][$this->table]['filter'] = $_SESSION['grids'][$this->table]['filters'][$load];
			return;
		}
		
		$filter = param("{$this->table}_filter");
		if (!is_null($filter)) {
			$_SESSION['grids'][$this->table]['filter'] = $filter;
			
			$save = param("{$this->table}_save");
			if ($save) {
				$_SESSION['grids'][$this->table]['filters'][$save] = $filter;
			}
		}
	}
	
	public function isFiltering() {
		return isset($_SESSION['grids'][$this->table]['filter']) && $_SESSION['grids'][$this->table]['filter'];
	}
	
	public function filterParam() {
		return coal($_SESSION['grids'][$this->table]['filter'], null);
	}
	
	public function savedFilterOptions() {
		return
			isset($_SESSION['grids'][$this->table]['filters'])
			? array_keys($_SESSION['grids'][$this->table]['filters'])
			: array();
	}
	
	protected function filterWildcard($filter) {
		return "{$filter}%";
	}
	
	protected function orderBySql() {
		if (!$this->sortColumns()) {
			return;
		}
		
		foreach ($this->sortColumns as $column=>$dir) {
			$this->sql->orderBy($column, self::dirToSql($dir));
		}
	}
	
	public static function dirToSql($dir) {
		return $dir == self::COL_DESC ? "desc" : "asc";
	}
}
