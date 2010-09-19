<?php

class ModelProxy {
	private $table;
	
	function __construct($table, $id) {
		$this->table = EvanceTable::fetch($table, $id);
	}
	
	public function __isset($field) {
		return isset($this->table->$field);
	}
	
	public function __get($field) {
		if (!isset($this->table->$field)) {
			trigger_error("Undefined field '{$field}'");
		}
		
		return $this->table->$field;
	}
	
	public function __set($field, $value) {
		if (!isset($this->table->$field)) {
			trigger_error("Undefined field '{$field}'");
		}
		
		$this->table->$field = $value;
	}
	
	public function save() {
		return EvanceTable::save($this->table);
	}
}

class Product extends ModelProxy {
	function __construct($id) {
		parent::__construct("products", $id);
	}
}
