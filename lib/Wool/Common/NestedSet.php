<?php

class NestedSet {
	private $config = array();
	private $positions = array(
		"before" => "moveBefore",
		"after" => "moveAfter",
		"first" => "moveToFirstChild",
		"last" => "moveToLastChild"
	);
	
	public function __construct($config) {
		$this->config = $config;
	}
	
	public function insertRoot($obj) {
		extract($this->config);
		
		$trans = new Transaction;
		
		// Save new node.
		$obj->$parent = 0;
		$obj->$left = 1;
		$obj->$right = 2;
		$obj->$root = 0;
		
		if (!WoolTable::save($obj)) {
			return false;
		}

		WoolDb::update($table, array($root=>$obj->$unique), "{$unique} = {$obj->$unique}");

		$trans->success();
		return true;
	}
	
	public function insertBefore($obj, $id) {
		extract($this->config);
		
		$trans = new Transaction;
		
		$node = $this->nestedData($id);
	
		// Update all higher nodes.
		$this->shiftNodes($node->$left, 2, $node->$root);
		
		// Save new node.
		$obj->$parent = $node->$parent;
		$obj->$left = $node->$left;
		$obj->$right = $node->$left + 1;
		$obj->$root = $node->$root;
		if (!WoolTable::save($obj)) {
			return false;
		}
		$trans->success();
		return true;
	}
	
	public function insertAfter($obj, $id) {
		extract($this->config);
		
		$trans = new Transaction;
		
		$node = $this->nestedData($id);
	
		// Update all higher nodes.
		$this->shiftNodes($node->$right + 1, 2, $node->$root);
		
		// Save new node.
		$obj->$parent = $node->$parent;
		$obj->$left = $node->$right + 1;
		$obj->$right = $node->$right + 2;
		$obj->$root = $node->$root;
		if (!WoolTable::save($obj)) {
			return false;
		}
		$trans->success();
		return true;
	}
	
	public function insertFirstChild($child, $id) {
		extract($this->config);
		
		$trans = new Transaction;
		
		$parentNode = $this->nestedData($id);
		
		// Update all higher nodes.
		$this->shiftNodes($parentNode->$left + 1, 2, $parentNode->$root);
		
		// Save new child.
		$child->$parent = $parentNode->$unique;
		$child->$left = $parentNode->$left + 1;
		$child->$right = $parentNode->$left + 2;
		$child->$root = $parentNode->$root;
		if (!WoolTable::save($child)) {
			return false;
		}
		
		$trans->success();
		return true;
	}
	
	public function insertLastChild($child, $id) {
		extract($this->config);
		
		$trans = new Transaction;
		
		$parentNode = $this->nestedData($id);
		
		// Update all higher nodes.
		$this->shiftNodes($parentNode->$right, 2, $parentNode->$root);
		
		// Save new child.
		$child->$parent = $parentNode->$unique;
		$child->$left = $parentNode->$right;
		$child->$right = $parentNode->$right + 1;
		$child->$root = $parentNode->$root;
		if (!WoolTable::save($child)) {
			return false;
		}
		
		$trans->success();
		return true;
	}

	public function moveRelative($obj, $id, $position) {
		$position = matchIndex($position, $this->positions);
		$func = $this->positions[$position];
		return $this->$func($obj, $id);
	}
	
	public function moveBefore($obj, $id) {
		extract($this->config);
		
		$obj = $this->nestedData($obj);
		$node = $this->nestedData($id);
		
		if ($node->$left < $obj->$left) {
			return $this->moveNode($obj, $node->$left, $node->$parent, true);
		}
		
		return $this->moveNode($obj, $node->$left-1, $node->$parent, false);
	}
	
	public function moveAfter($obj, $id) {
		extract($this->config);
		
		$obj = $this->nestedData($obj);
		$node = $this->nestedData($id);
		
		if ($node->$right == $obj->$left - 1) {
			return true;
		}
		
		if ($node->$left < $obj->$left) {
			return $this->moveNode($obj, $node->$right+1, $node->$parent, true);
		}
		
		return $this->moveNode($obj, $node->$right, $node->$parent, false);
	}
	
	public function moveToFirstChild($obj, $id) {
		extract($this->config);
		
		$obj = $this->nestedData($obj);
		$node = $this->nestedData($id);
		
		if ($node->$left < $obj->$left) {
			return $this->moveNode($obj, $node->$left+1, $node->$unique, true);
		}
		
		return $this->moveNode($obj, $node->$left, $node->$unique, false);
	}
	
	public function moveToLastChild($obj, $id) {
		extract($this->config);
		
		$obj = $this->nestedData($obj);
		$node = $this->nestedData($id);
		
		if ($node->$left < $obj->$left) {
			return $this->moveNode($obj, $node->$right, $node->$unique, true);
		}
		
		return $this->moveNode($obj, $node->$right-1, $node->$unique, false);
	}
	
	public function removeNode($id) {
		extract($this->config);
		
		$node = $this->nestedData($id);
		if (!$node) { return; }
		
		WoolDb::delete($table, "{$root} = {$node->$root} and {$left} >= {$node->$left} and {$right} <= {$node->$right}");
		$this->shiftNodes($node->$left, $node->$left - $node->$right - 1, $node->$root);
	}
	
	private function moveNode($obj, $dest, $parentId, $moveLeft) {
		extract($this->config);
		
		$trans = new Transaction;

		$rootNode = $this->nestedData($obj->$root);
		$branchSize = $obj->$right - $obj->$left + 1;
		$shiftDist = $rootNode->$right - $obj->$left + 1;
		
		$this->shiftNodesRange($obj->$left, $obj->$right, $shiftDist, $obj->$root);
		
		$shiftLeft = $obj->$left + $shiftDist;
		$shiftRight = $obj->$right + $shiftDist;
		
		if ($moveLeft) {
			$this->shiftNodesRange($dest, $obj->$left-1, $branchSize, $obj->$root);
			$this->shiftNodesRange($shiftLeft, $shiftRight, $dest-$shiftLeft, $obj->$root);
		} else {
			$this->shiftNodesRange($obj->$right+1, $dest, -$branchSize, $obj->$root);
			$this->shiftNodesRange($shiftLeft, $shiftRight, $dest-$shiftRight, $obj->$root);
		}

		// Update parent relation.
		WoolDb::update($table, array($parent=>$parentId), array($unique=>$obj->$unique));
		
		$trans->success();
		return true;
	}
	
	private function shiftNodes($lowest, $delta, $rootId) {
		extract($this->config);

		WoolDb::paramQuery(<<<SQL
update {$table}
set {$left} = {$left} + {$delta}
where {$root} = ? and {$left} >= ?
SQL
		, array($rootId, $lowest));
		
		WoolDb::paramQuery(<<<SQL
update {$table}
set {$right} = {$right} + {$delta}
where {$root} = ? and {$right} >= ?
SQL
		, array($rootId, $lowest));
	}
	
	private function shiftNodesRange($lowest, $highest, $delta, $rootId) {
		extract($this->config);
		
		WoolDb::paramQuery(<<<SQL
update {$table}
set {$left} = {$left} + {$delta}
where {$root} = ? and {$left} >= ? and {$left} <= ?
SQL
		, array($rootId, $lowest, $highest));
		
		WoolDb::paramQuery(<<<SQL
update {$table}
set {$right} = {$right} + {$delta}
where {$root} = ? and {$right} >= ? and {$right} <= ?
SQL
		, array($rootId, $lowest, $highest));
	}
	
	private function nestedData($id) {
		extract($this->config);
		
		if (!is_numeric($id)) {
			return $id;
		}
		
		return WoolDb::fetchRow(<<<SQL
select {$unique}, {$left}, {$right}, {$parent}, {$root}
from {$table}
where {$unique} = ?
SQL
		, $id);
	}
	
	protected function config() {
		return $this->$config;
	}
	
	// Rebuild from adjacency list.
	private function rebuild($parentId, $lft) {
		extract($this->config);
		
		$rgt = $lft+1;
		
		$children = WoolDb::fetchAll("select {$parent} FROM {$table} where {$parent}={$parentId}");
		
		foreach ($children as $child) {
			$rgt = $this->rebuild($child->$parent, $rgt);
		}

		WoolDb::update(
			$table,
			array($left => $lft, $right => $rgt),
			array("{$unique} = ?" => $parentId)
		);
		
		return $rgt+1;
	}
}

