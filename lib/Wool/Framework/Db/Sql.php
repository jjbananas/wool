<?php

// Build and manipulate sql statements.
class Sql {
	private $sql;
	private $params;
	
	private $split = false;
	private $parts = array();
	private $partParams = array();
	
	public function __construct($sql, $params=array()) {
		$this->sql = $sql;
		$this->params = is_array($params) ? $params : array($params);
	}
	
	public function toString() {
		if ($this->sql) {
			return $this->sql;
		}
		
		foreach (array("select", "from", "where", "group by", "having", "order", "limit") as $part) {
			if (isset($this->parts[$part]) && $this->parts[$part]) {
				$this->sql .= $this->parts[$part] . "\n" ;
			}
		}
		
		return $this->sql;
	}
	
	// Attempt to show the string with parameters in place.
	public function debugString() {
		$string = $this->toString();
		foreach ($this->params() as $param) {
			$string = preg_replace('/\?/', $param, $string, 1);
		}
		return $string;
	}
	
	public function params() {
		if ($this->params) {
			return $this->params;
		}
		
		$this->params = array();
		
		foreach (array("select", "from", "where", "group by", "having", "order", "limit") as $part) {
			if (isset($this->partParams[$part]) && $this->partParams[$part]) {
				$this->params = array_merge($this->params, $this->partParams[$part]);
			}
		}
		
		return $this->params;
	}
	
	public function rowSet() {
		return new RowSet($this->toString(), $this->params());
	}
	
	public function fetchRow() {
		return WoolTable::fetchRow($this->toString(), $this->params());
	}
	
	public function count() {
		$this->split();
		
		$countSql = "select count(*)\n";
		
		foreach (array("from", "where", "group by", "having") as $part) {
			if (isset($this->parts[$part]) && $this->parts[$part]) {
				$countSql .= $this->parts[$part] . "\n" ;
			}
		}
		
		$params = array();
		
		foreach (array("from", "where", "group by", "having") as $part) {
			if (isset($this->partParams[$part]) && $this->partParams[$part]) {
				$params = array_merge($params, $this->partParams[$part]);
			}
		}
		
		return WoolDb::fetchOne($countSql, $params);
	}
	
	public function __call($name, $params) {
		if ($this->params()) {
			array_unshift($params, $this->params()); 
		}
		array_unshift($params, $this->toString()); 
		return call_user_func_array(array('WoolDb', $name), $params);
	}
	
	public function select($column) {
		$this->split();
		$this->parts['select'] = "select {$column}";
		return $this;
	}
	
	public function andSelect($column) {
		$this->addCommaSeparated("select", $column);
		return $this;
	}
	
	public function join($join, $params=null) {
		$this->innerJoin($join, $params);
		return $this;
	}
	
	public function innerJoin($join, $params=null) {
		$this->split();
		$this->parts['from'] .= "\njoin {$join}";
		$this->addParams("from", $params);
		return $this;
	}
	
	public function leftJoin($join, $params=null) {
		$this->split();
		$this->parts['from'] .= "\nleft join {$join}";
		$this->addParams("from", $params);
		return $this;
	}

	public function where($where, $params=null) {
		$this->split();
		$this->parts['where'] = "where {$where}";
		$this->partParams['where'] = $params;
		return $this;
	}
	
	public function andWhere($where, $params=null) {
		$this->addOptional("where", "and", $where, $params);
		$this->addParams("where", $params);
		return $this;
	}
	
	public function orWhere($where, $params=null) {
		$this->addOptional("where", "or", $where, $params);
		$this->addParams("where", $params);
		return $this;
	}
	
	public function groupBy($column) {
		$this->split();
		$this->parts['group by'] = "group by {$column}";
		return $this;
	}
	
	public function andGroupBy($column) {
		$this->addCommaSeparated("group by", $column);
		return $this;
	}
	
	public function having($where, $params=null) {
		$this->split();
		$this->parts['having'] = "having {$where}";
		$this->partParams['having'] = $params;
		return $this;
	}
	
	public function andHaving($where, $params=null) {
		$this->addOptional("having", "and", $where, $params);
		$this->addParams("having", $params);
		return $this;
	}
	
	public function orHaving($where, $params=null) {
		$this->addOptional("having", "or", $where, $params);
		$this->addParams("having", $params);
		return $this;
	}
	
	public function orderBy($column, $dir=null) {
		$this->split();
		$dir = $dir ? ' ' . $dir : '';
		
		if($this->parts['order'] === false){
			$this->parts['order'] = "order by {$column} {$dir}";
		} else {
			$this->parts['order'] .= ", {$column} {$dir}";
		}
		return $this;
	}
	
	public function limit($a, $b=null) {
		$this->split();
		$b = $b ? ', ' . $b : '';
		$this->parts['limit'] = "limit {$a}{$b}";
		return $this;
	}
	
	private function addOptional($section, $type, $clause, $params) {
		$this->split();
		
		if (!$this->parts[$section]) {
			$this->parts[$section] = "{$section}";
			$type = '';
		}
		
		$this->parts[$section] .= " {$type} {$clause}";
	}
	
	private function addCommaSeparated($section, $column) {
		$this->split();
		
		$comma = ", ";
		
		if (!$this->parts[$section]) {
			$this->parts[$section] = "{$section} ";
			$comma = "";
		}
		
		$this->parts[$section] .= "{$comma}{$column}";
	}
	
	private function addParams($section, $params) {
		if (is_null($params)) {
			return;
		}
		
		$params = is_array($params) ? $params : array($params);
		if (!isset($this->partParams[$section])) {
			$this->partParams[$section] = $params;
			return;
		}
		$this->partParams[$section] = array_merge($this->partParams[$section], $params);
	}
	
	private function split() {
		$sql = $this->sql;
		$this->sql = null;
		
		if ($this->split) {
			return;
		}
		$this->split = true;
		
		$parser = new SqlParser($sql);
		$parser->parse();
		$this->parts = $parser->sqlParts;
		
		$offset = 0;
		foreach ($parser->paramParts as $part=>$num) {
			$this->partParams[$part] = array_slice($this->params, $offset, $num);
			$offset += $num;
		}
		
		$this->params = array();
	}
}

function Query($sql, $params=array()) {
	return new Sql($sql, $params);
}
