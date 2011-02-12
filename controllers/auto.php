<?php

require_once('Wool/App/Product.php');

class AutoController extends Controller {
	const COL_HIDDEN = 0;
	const COL_NORMAL = 1;
	const COL_ASC = 2;
	const COL_DESC = 3;
	
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
					$this->columns[$col] = $val;
				}
			}
		} else {
			$this->columns = array();
			foreach ($this->allColumns as $col) {
				$this->columns[$col] = self::COL_NORMAL;
			}
		}
	}
	
	function adminEdit() {
		$this->table = param('table');
		$this->item = WoolTable::fetch($this->table, "id", "item");
		$this->columns = WoolTable::editableColumns($this->table);
		$this->derivedColumns = WoolTable::derivedColumns($this->table);
		
		$this->foreign = array();
		foreach (WoolTable::columns("cart_line") as $col) {
			$this->foreign["cart_line"]["columns"][$col] = self::COL_NORMAL;
		}
		$this->foreign["cart_line"]["data"] = new WoolGrid("cart_line", <<<SQL
select * from cart_line where cartId = {$this->item->cartId}
SQL
		);
		
		
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
	
	function adminKeySearch() {
		$this->data();
		$class = WoolTable::tableClass($this->table);
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
		$grid = isset($_SESSION['grids'][$table]) ? $_SESSION['grids'][$table] : array();
		$new = array();
		
		foreach (param('cols', array()) as $col) {
			$new[$col] = isset($grid[$col]) ? $grid[$col] : COL_NORMAL;
		}
		
		foreach (WoolTable::columns($table) as $col) {
			if (!isset($new[$col])) {
				$new[$col] = isset($grid[$col]) ? $grid[$col] : COL_NORMAL;
			}
		}
		
		$_SESSION['grids'][$table] = $new;
		
		$this->renderJson(array(
			"success" => true
		));
	}
	
	function adminReset() {
		$this->data();
		$_SESSION['grids'][$this->table] = null;
		$this->redirectTo(array("action"=>"table", "table"=>$this->table));
	}
	
	function adminColumnSelect() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$table = param('table');
		$visible = param('cols', array());
		
		foreach (WoolTable::columns($table) as $col) {
			if (!isset($_SESSION['grids'][$table][$col])) {
				$_SESSION['grids'][$table][$col] = COL_NORMAL;
			}
		}
		
		foreach ($_SESSION['grids'][$table] as $col=>$val) {
			$_SESSION['grids'][$table][$col] = isset($visible[$col]) ? COL_NORMAL : COL_HIDDEN;
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
