<?php

class RowProxy {
	private $obj;
	private $cols = array();
	
	public function __construct($sourceObj, $allowedColumns) {
		$this->obj = $sourceObj;
		$this->cols = $allowedColumns;
	}
	
	public function __isset($field) {
		return isset($this->cols[$field]);
	}
	
	public function __get($field) {
		if (isset($this->cols[$field])) {
			$alias = $this->cols[$field];
			return $this->obj->$alias;
		}
		
		trigger_error("Undefined field '{$field}'");
	}
	
	public function __set($field, $value) {
		if (isset($this->cols[$field])) {
			$alias = $this->cols[$field];
			$this->obj->$alias = $value;
			return;
		}
		
		trigger_error("Undefined field '{$field}'");
	}
}

class RowSet implements IteratorAggregate, Countable, ArrayAccess {
	private $rows = array();
	private $indices = array();
	private $groups = array();
	
	public function __construct($source, $params=array()) {
		if (is_string($source)) {
			$result = $this->fetchAcross($source, $params);
			$this->rows = $result['rows'];
			$meta = new SqlMeta($result['source']);
			foreach ($this->rows as $row) {
				WoolTable::setQueryMeta($row, $meta);
			}
		} else {
			$this->rows = $source;
		}
	}
	
	private function fetchAcross($source, $params) {
		$query = WoolDb::joinAcross($source, $params);
		return array('source'=>$query['sql'], 'rows'=>WoolDb::fetchAll($query['sql'], $query['params']));
	}
	
	public function rows() {
		return $this->rows;
	}
	
	// IteratorAggregate interface.
	public function getIterator() {
		return new ArrayIterator($this->rows);
	}
	
	// Countable interface.
	public function count() {
		return count($this->rows);
	}
	
	// ArrayAccess interface.
	public function offsetSet($offset, $value) {
		$this->rows[$offset] = $value;
	}
	public function offsetExists($offset) {
		return isset($this->rows[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->rows[$offset]);
	}
	public function offsetGet($offset) {
		return isset($this->rows[$offset]) ? $this->rows[$offset] : null;
	}

	
	// Get a row or row set by an indexed column.
	public function by($column, $value=null, $strict=false) {
		if (!isset($this->indices[$column])) {
			if ($strict) {
				trigger_error("Attempting to access non-indexed column", E_USER_ERROR);
			}
			$this->index($column);
		}
		
		$index = coal($this->indices[$column], array());
		
		if (is_null($value)) {
			return $index;
		}
		
		return coal($index[$value], null);
	}
	
	// Lookup by group.
	public function byGroup($column, $value=null, $strict=false) {
		if (!isset($this->groups[$column])) {
			if ($strict) {
				trigger_error("Attempting to access non-indexed column", E_USER_ERROR);
			}
			$this->groupBy($column);
		}
		
		$group = coal($this->groups[$column], array());
		
		if (is_null($value)) {
			return $group;
		}
		
		return coal($group[$value], array());
	}

	public function value($column) {
		if (!isset($this->rows[0])) {
			return null;
		}

		return $this->rows[0]->$column;
	}
	
	public function valueBy($column, $value, $field) {
		$row = $this->by($column, $value);
		return $row ? $row->$field : null;
	}
	
	// Create groups on the column(s) provided.
	public function groupBy(/*...*/) {
		$groups = func_get_args();
		foreach ($groups as $group) {
			$sets = array();
			foreach ($this->rows as &$row) {
				$sets[$row->$group][] = &$row;
			}
			
			foreach ($sets as $name => $set) {
				$this->groups[$group][$name] = new RowSet($set);
			}
		}
	}
	
	// Index the column(s) provided.
	public function index(/*...*/) {
		$columns = func_get_args();
		foreach ($columns as $column) {
			if (isset($this->indices[$column])) { continue; }
			$this->indices[$column] = array();
			
			foreach ($this->rows as &$row) {
				if (isset($this->indices[$column][$row->$column])) { continue; }
				$this->indices[$column][$row->$column] = &$row;
			}
		}
	}
}
