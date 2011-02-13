<?php

class SqlMeta {
	private $selects = array();
	private $tables = array();
	private $tableSelects = array();
	
	public function __construct($source, $singleTable=false) {
		if ($singleTable) {
			$selects[] = array('source'=>'*', 'alias'=>null, 'table'=>$source);
			$tables[] = array('source'=>$source, 'alias'=>null); 
		} else {
			$parser = new SqlParser($source);
			$parser->parse();
			$selects = $parser->selects;
			$tables = $parser->sourceTables;
		}
		
		foreach ($tables as $table) {
			$alias = $table['alias'] ? $table['alias'] : $table['source'];
			$this->tables[$alias] = $table['source'];
		}

		foreach ($selects as $select) {
			if ($select['source'] == '*') {
				if ($select['table']) {
					$table = $this->realTable($select['table']);
					$schema = Schema::allColumns($table);
					foreach ($schema as $colName=>$col) {
						if (isset($this->selects[$colName])) {
							trigger_error("Attempting to select multiple columns with the name '{$colName}'.", E_USER_WARNING);
						}
						$this->selects[$colName] = array('source'=>$colName, 'alias'=>$colName, 'table'=>$select['table'], 'srcTable'=>$table);
						$this->tableSelects[$select['table']][$colName] = $colName;
					}
				} else {
					foreach ($this->sourceTables() as $table=>$srcTable) {
						$schema = Schema::allColumns($srcTable);
						foreach ($schema as $name=>$col) {
							if (isset($this->selects[$name])) {
								trigger_error("Attempting to select multiple columns with the name '{$name}'.", E_USER_WARNING);
							}
							$this->selects[$name] = array('source'=>$name, 'alias'=>$name, 'table'=>$table, 'srcTable'=>$srcTable);
							$this->tableSelects[$table][$name] = $name;
						}
					}
				}
			}
			else {
				if (!$select['table']) {
					foreach ($this->tables as $alias=>$table) {
						$schema = Schema::allColumns($table);
						if (isset($schema[$select['source']])) {
							$select['table'] = $alias;
						}
					}
				}
				$select['srcTable'] = $this->realTable($select['table']);
				$select['alias'] = coal($select['alias'], $select['source']);
				if (isset($this->selects[$select['alias']])) {
					trigger_error("Attempting to select multiple columns with the name '{$select['alias']}'.", E_USER_WARNING);
				}
				$this->selects[$select['alias']] = $select;
				$this->tableSelects[$select['table']][$select['source']] = $select['alias'];
			}
		}
	}
	
	public function selects() {
		return $this->selects;
	}
	
	public function realTable($alias) {
		return isset($this->tables[$alias]) ? $this->tables[$alias] : null;
	}
	
	public function sourceTables() {
		return $this->tables;
	}
	
	public function columnAlias($table, $name) {
		return isset($this->tableSelects[$table][$name]) ? $this->tableSelects[$table][$name] : null;
	}
	
	public function columnSource($alias) {
		return isset($this->selects[$alias]['source']) ? $this->selects[$alias]['source'] : null;
	}
	
	public function columnTable($alias) {
		return isset($this->selects[$alias]['srcTable']) ? $this->selects[$alias]['srcTable'] : null;
	}
	
	public function columnsForTable($table) {
		return $this->tableSelects[$table];
	}
}
