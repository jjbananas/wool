<?php

require_once('Wool/App/Product.php');

class AutoController extends Controller {
	function startUp() {
		$this->addHelper('grid');
	}
	
	private function addAutoMedia($table, $action) {
		$this->addAvailableMedia($table . '/common');
		$this->addAvailableMedia($table . '/' . $this->portalName);
		$this->addAvailableMedia($table . '/common_' . $action);
		$this->addAvailableMedia($table . '/' . $this->portalName . '_' . $action);
	}
	
	function adminIndex() {
		$this->tables = Schema::tables();
	}
	
	function adminTable() {
		$this->table = param('table');
		$this->addAutoMedia($this->table, "index");
		
		$this->grid = new WoolAutoGrid($this->table);
		$this->grid->setPerPage(25);
		
		$this->item = WoolTable::fetch($this->table, "id", "item");
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
					$this->foreign[$key]["columns"][$col] = 1;
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
	
	function adminGridAction() {
		if (!Request::isPost()) {
			return;
		}
		
		$this->table = param('table');
		
		if (param('delete')) {
			foreach (param('item', array()) as $id=>$_) {
				WoolTable::delete($this->table, $id);
			}
			$this->redirectTo(array("action"=>"table", "table"=>$this->table));
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
	
	function adminJoin() {
		$this->foreign = param('foreign');
		$isJoin = Schema::isJoinTable($this->foreign);
		
		if ($isJoin) {
			$this->foreign = array_shift(Schema::referencedTables($this->foreign, $this->table));
		}
		
		if (Request::isPost()) {
			if ($isJoin) {
				$ids = array_keys(param('item', array()));
				//WoolDb::update($this->foreign, array(), );
			} else {
				foreach (param('item', array()) as $id=>$_) {
				}
			}
			$this->redirectTo(Request::uri());
		}
		
		$this->grid = new WoolAutoGrid($this->foreign);
		$this->grid->setPerPage(25);
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
		$grid = new WoolAutoGrid($table);
		$grid->setColumnPositions(param('cols', array()));
		
		$this->renderJson(array("success" => true));
	}
	
	function adminReset() {
		$_SESSION['grids'][$this->table]['cols'] = null;
		$_SESSION['grids'][$this->table]['sort'] = null;
		$this->redirectTo(array("action"=>"table", "table"=>$this->table));
	}
	
	function adminColumnSelect() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$grid = new WoolAutoGrid($table);
		$grid->setVisibleColumns(array_keys(param('cols', array())));
		
		$this->redirectTo(array("action"=>"index", "table"=>$table));
	}
	
	function adminColumnSort() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$grid = new WoolAutoGrid($table);
		
		$sorts = array();
		
		foreach (param('cols', array()) as $num=>$vals) {
			$sorts[$vals["sort"]] = ($vals["dir"] == "asc" ? WoolAutoGrid::COL_ASC : WoolAutoGrid::COL_DESC);
		}
		
		$grid->setColumnSorts($sorts);
		
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
			$json['html'] = $this->renderToString($this->table . "_row_form_partial", null, null);
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
		$unique = Schema::uniqueColumn($table);
		$uniqueVal = param('unique');
		
		$json = array();
		
		if (WoolTable::validateColumn($table, $column, $value, $this)) {
			$json['success'] = WoolDb::update($table, array($column=>$value), array("{$unique} = ?"=>$uniqueVal));
			$json['value'] = $value;
		} else {
			$json['msg'] = "Update failed.";
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
