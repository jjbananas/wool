<?php

class WoolErrors {
	private static $errors = array();
	
	public static function add($id, $field, $message) {
		if (is_object($id)) {
			$id = spl_object_hash($id);
		}
		
		if (!is_string($id)) {
			trigger_error("id should be a string or object.", E_USER_ERROR);
		}
		
		self::$errors[$id][$field][] = $message;
	}
	
	public static function get($id=null, $field=null) {
		if (!$id) {
			return self::$errors;
		}
		
		if (is_object($id)) {
			$id = spl_object_hash($id);
		}
		
		$errors = coal(self::$errors[$id], array());
		
		if (!$field) {
			return $errors;
		}
		
		return coal($errors[$field], array());
	}
}
