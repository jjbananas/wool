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
				$condition = Schema::primaryCondition($key, $this->table, $this->item, "t");
				$this->foreign[$key] = new WoolAutoGrid($key, $condition);
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
		$this->table = param('table');
		$this->historyTable = "history_{$this->table}";

		$this->item = WoolTable::fetch($this->table, "id");
		$this->columns = Schema::historySummaryColumns($this->historyTable);

		$this->data = new WoolGrid(
			$this->historyTable,
			WoolTable::queryJoined(
				$this->historyTable,
				$this->columns,
				Schema::primaryCondition($this->historyTable, $this->table, $this->item, "t")
			)
		);
	}

	function adminRevert() {
		$this->table = param('table');
		$this->item = WoolTable::fetch($this->table, "id");
		$this->columns = Schema::editableColumns($this->table);
		$this->derivedColumns = Schema::derivedColumns($this->table);

		$this->historyItem = WoolTable::fetch("history_{$this->table}", 1);

		foreach ($this->item as $field=>&$value) {
			$newField = "new_{$field}";

			if (isset($this->historyItem->$newField)) {	
				$value = $this->historyItem->$newField;
			}
		}

		WoolTable::fromArray($this->item, "item");

		if (Request::isPost()) {
			if (WoolTable::save($this->item)) {
				$this->redirectTo(array("action"=>"table", "table"=>$this->table));
			}
		}
	}
	
	function adminTree() {
		$this->table = param('table');

		$this->tree = new RowSet(<<<SQL
select pageDirectoryId id, parentId parentId, title title
from page_directory
SQL
		);
		
		$this->item = WoolTable::fetch("page_directory", "id");
		$this->columns = Schema::editableColumns($this->table);
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
		$this->table = param('table');
		$this->foreign = param('foreign');

		$isJoin = Schema::isJoinTable($this->foreign);
		
		if ($isJoin) {
			$this->foreign = array_shift(Schema::referencedTables($this->foreign, $this->table));
		}
		
		$this->addAutoMedia($this->foreign, "index");
		
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
		
		$this->grid = new WoolAutoGrid($this->foreign, null, param('foreign'), id_param('id'), $isJoin ? $this->table : null);
		$this->grid->setPerPage(25);
	}
	
	function adminKeySearch() {
		$this->table = param('table');
		
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
		
		$this->table = param('table');
		$this->grid = new WoolAutoGrid($this->table);
		$this->item = WoolTable::fetch($this->table, "id", "item");
		$json = array();
		
		if (WoolTable::save($this->item)) {
			$json['success'] = true;
			$this->selected = true;
			$json['html'] = $this->renderToString("row_partial", null, null);
		} else {
			$json['success'] = false;
			$json['html'] = $this->renderToString("/{$this->table}/auto_row_form_partial", null, null);
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
