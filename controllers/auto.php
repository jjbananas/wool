<?php

class AutoController extends Controller {
	function startUp() {
		$this->addHelper('grid');
	}
	
	private function data() {
		$this->table = param('table');
		$this->allColumns = WoolTable::columns($this->table);
		$this->columns = $this->allColumns;
	}
	
	function adminIndex() {
		$this->tables = WoolTable::tableList();
	}
	
	function adminTable() {
		$this->data();
		
		$this->data = new WoolGrid($this->table, <<<SQL
select * from {$this->table}
SQL
		);
		$this->data->setPerPage(3);
		
		if (isset($_SESSION['grids'][$this->table])) {
			$this->columns = array();
			foreach ($_SESSION['grids'][$this->table] as $col=>$val) {
				if ($val) {
					$this->columns[] = $col;
				}
			}
		} else {
			$this->columns = $this->allColumns;
		}
	}
	
	function adminEdit() {
		$this->table = param('table');
		$this->item = WoolTable::fetch($this->table, "id", "item");
		$this->columns = WoolTable::editableColumns($this->table);
		$this->derivedColumns = WoolTable::derivedColumns($this->table);
		
		if (Request::isPost()) {
			if (WoolTable::save($this->item)) {
				$this->redirectTo(array("action"=>"table", "table"=>$this->table));
			}
		}
	}
	
	function adminDelete() {
		$this->table = param('table');
		$this->item = WoolTable::fetch($this->table, "id", "item");
		
		if (Request::isPost()) {
			if (WoolDb::delete($table, "id")) {
				$this->redirectTo(array("action"=>"table", "table"=>$this->table));
			}
		}
	}
	
	function adminHistory() {
		$this->table = param('table');
		$this->item = WoolTable::fetch($this->table, "id");
		
		if (Request::isPost()) {
			
		}
	}
	
	function adminHeaderUpdate() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$grid = isset($_SESSION['grids'][$table]) ? $_SESSION['grids'][$table] : array();
		$new = array();
		
		foreach (param('cols', array()) as $col) {
			$new[$col] = isset($grid[$col]) ? $grid[$col] : false;
		}
		
		foreach (WoolTable::columns($table) as $col) {
			if (!isset($new[$col])) {
				$new[$col] = true;
			}
		}
		
		$_SESSION['grids'][$table] = $new;
		
		$this->renderJson(array(
			"success" => true
		));
	}
	
	function adminColumnSelect() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$visible = param('cols', array());
		
		foreach (WoolTable::columns($table) as $col) {
			if (!isset($_SESSION['grids'][$table][$col])) {
				$_SESSION['grids'][$table][$col] = true;
			}
		}
		
		foreach ($_SESSION['grids'][$table] as $col=>$val) {
			$_SESSION['grids'][$table][$col] = isset($visible[$col]);
		}
		
		$this->redirectTo(array("action"=>"index", "table"=>$table));
	}
	
	function adminRowInsert() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$this->data();
		$this->item = WoolTable::fetch($this->table, "id", "item");
		$json = array();
		
		if (true) { //WoolTable::save($this->item)) {
			$json['success'] = true;
			$this->selected = true;
			$json['html'] = $this->renderToString("row_partial", null, null);
		} else {
			$json['success'] = false;
			$json['errors'] = WoolErrors::get();
		}
		
		$this->renderJson($json);
	}
	
	function adminColumnUpdate() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$column = param('column');
		$value = param('value');
		$unique = "userId";
		$uniqueVal = param('unique');
		
		$json = array();
		
		if (WoolTable::validateColumn($table, $column, $value, $this)) {
			$json['success'] = WoolDb::update($table, array($column=>$value), array("{$unique} = ?"=>$uniqueVal));
			$json['value'] = $value;
		} else {
			$json['success'] = false;
		}
		
		$this->renderJson($json);
	}
	
	function adminRowOrder() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$src = param('src');
		$dst = param('dst');
		$before = param('before', false);
		
		$this->renderJson(array(
			'success' => true
		));
	}
	
	private function ensurePost() {
		if (!Request::isPost()) {
			$this->renderJson(array(
				'success' => false,
				'msg' => "Request must be POST"
			));
			return false;
		}
		
		return true;
	}
}
