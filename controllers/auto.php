<?php

require_once('Wool/App/Product.php');

class AutoController extends Controller {
	const COL_HIDDEN = 0;
	const COL_NORMAL = 1;
	
	function startUp() {
		$this->addHelper('grid');
	}
	
	private function data() {
		$this->table = param('table');
		$this->allColumns = Schema::summaryColumns($this->table);
		$this->columns = array();
		foreach ($this->allColumns as $col) {
			$this->columns[$col] = self::COL_NORMAL;
		}
	}
	
	function adminIndex() {
		$this->tables = Schema::tables();
	}
	
	function adminTable() {
		$this->data();
		
		$search = param('search');
		$this->data = new WoolAutoGrid($this->table);
		$this->data->setPerPage(25);

		if (isset($_SESSION['grids'][$this->table]['cols'])) {
			$this->columns = array();
			foreach ($_SESSION['grids'][$this->table]['cols'] as $col=>$val) {
				if ($val) {
					$this->columns[$col] = $val;
				}
			}
		}
		
		$this->sortColumns = coal($_SESSION['grids'][$this->table]['sort'], array());
	}
	
	function adminEdit() {
		$this->table = param('table');
		$this->item = WoolTable::fetch($this->table, "id", "item");
		$this->columns = Schema::editableColumns($this->table);
		$this->derivedColumns = Schema::derivedColumns($this->table);
		
		$this->foreign = array();
		$u = Schema::uniqueColumn($this->table);
		if ($this->item->$u) {
			foreach (Schema::inboundKeys($this->table) as $key) {
				foreach (Schema::columns($key) as $col) {
					$this->foreign[$key]["columns"][$col] = self::COL_NORMAL;
				}
				$condition = Schema::primaryCondition($key, $this->table, $this->item, "f");
				$this->foreign[$key]["data"] = new WoolGrid($key, <<<SQL
select * from {$key} f where {$condition}
SQL
				);
			}
		}
		
		
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
			if (WoolTable::delete($this->table, "id")) {
				$this->redirectTo(array("action"=>"table", "table"=>$this->table));
			}
		}
	}
	
	function adminHistory() {
		$this->data();
		$this->item = WoolTable::fetch($this->table, "id");
		$this->data = new WoolGrid("history_{$this->table}", <<<SQL
select h.productId, h.changedOn, h.cause, new_price price, new_title title, taxId
from history_product h
join product s on s.productId = h.productId
where h.productId = ?
order by h.changedOn desc
SQL
		, $this->item->productId);
		
		if (Request::isPost()) {
			
		}
	}
	
	function adminTree() {
		$this->data();
		
		$this->tree = new RowSet(<<<SQL
select pageDirectoryId id, parentId parentId, title title
from page_directory
SQL
		);
		
		$this->item = WoolTable::fetch("page_directory", "id");
		$this->derivedColumns = Schema::derivedColumns($this->table);
		
		if (Request::isAjax()) {
			$this->renderJson(array(
				"success" => true,
				"item" => $this->item
			));
			return;
		}
	}
	
	function adminKeySearch() {
		$this->data();
		$class = Schema::tableClass($this->table);
		if (method_exists($class, "keySearch")) {
			$this->matches = call_user_func(array($class, "keySearch"), param('search'));
		} else {
			$this->matches = WoolTable::keySearch($this->table, param('search'));
		}
		
		if ($this->canRenderPartial("/{$this->table}/keysearch")) {
			$this->renderPartial("/{$this->table}/keysearch");
		} else {
			$this->renderPartial('keysearch');
		}
	}
	
	function adminHeaderUpdate() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$grid = isset($_SESSION['grids'][$table]['cols']) ? $_SESSION['grids'][$table]['cols'] : array();
		$new = array();
		
		foreach (param('cols', array()) as $col) {
			$new[$col] = isset($grid[$col]) ? $grid[$col] : self::COL_NORMAL;
		}
		
		foreach (Schema::columns($table) as $col) {
			if (!isset($new[$col])) {
				$new[$col] = isset($grid[$col]) ? $grid[$col] : self::COL_NORMAL;
			}
		}
		
		$_SESSION['grids'][$table]['cols'] = $new;
		
		$this->renderJson(array(
			"success" => true
		));
	}
	
	function adminReset() {
		$this->data();
		$_SESSION['grids'][$this->table]['cols'] = null;
		$_SESSION['grids'][$this->table]['sort'] = null;
		$this->redirectTo(array("action"=>"table", "table"=>$this->table));
	}
	
	function adminColumnSelect() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$visible = param('cols', array());
		
		foreach (Schema::columns($table) as $col) {
			if (!isset($_SESSION['grids'][$table]['cols'][$col])) {
				$_SESSION['grids'][$table]['cols'][$col] = self::COL_NORMAL;
			}
		}
		
		foreach ($_SESSION['grids'][$table]['cols'] as $col=>$val) {
			$_SESSION['grids'][$table]['cols'][$col] = isset($visible[$col]) ? self::COL_NORMAL : self::COL_HIDDEN;
		}
		
		$this->redirectTo(array("action"=>"index", "table"=>$table));
	}
	
	function adminColumnSort() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$cols = param('cols', array());
		
		$allowedCols = Schema::columns($table);
		
		$_SESSION['grids'][$table]['sort'] = array();
		
		debug($cols);
		
		foreach ($cols as $col) {
			if (!in_array($col["sort"], $allowedCols)) {
				continue;
			}
			
			$_SESSION['grids'][$table]['sort'][$col["sort"]] = matchSqlOrder($col["dir"]);
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
		
		if (WoolTable::save($this->item)) {
			$json['success'] = true;
			$this->selected = true;
			$json['html'] = $this->renderToString("row_partial", null, null);
		} else {
			$json['success'] = false;
			$json['html'] = $this->renderToString("users_row_form_partial", null, null);
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
