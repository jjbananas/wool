<?php

class AutoController extends Controller {
	function startUp() {
		$this->addHelper('grid');
	}
	
	private function data() {
		$this->table = "users";
		$this->columns = array(
			"name", "email", "password", "avatar"
		);
	}
	
	function adminIndex() {
		$this->data();
		
		$this->data = new WoolGrid($this->table, <<<SQL
select * from users
SQL
		);
		$this->data->setPerPage(3);
		
		$this->columns = isset($_SESSION['grids'][$this->table]) ? $_SESSION['grids'][$this->table] : $this->columns;
	}
	
	function adminEdit() {
		$this->data();

		$this->item = WoolTable::fetch($this->table, "id", "item");
		
		if (Request::isPost()) {
			if (WoolTable::save($this->item)) {
				$this->redirectTo(array("action"=>"index"));
			}
		}
	}
	
	function adminHeaderUpdate() {
		if (!$this->ensurePost()) {
			return;
		}
		
		$_SESSION['grids'][param('table')] = param('cols');
		$this->renderJson(array(
			"success" => true
		));
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
