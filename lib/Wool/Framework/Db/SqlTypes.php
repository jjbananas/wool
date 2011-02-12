<?php

// MySQL type information.
class SqlTypes {
	private static $types = array(
		"varchar" => array(
		),
		
		"int" => array(
		),
		
		"decimal" => array(
		),
		
		"datetime" => array(
		),
		
		"enum" => array(
		),
		
		"float" => array(
		)
	);
	
	public function isValidDataType($type) {
		return isset(self::$types[$type]);
	}
	
	public function isText($type) {
		return self::$types[$type]["text"];
	}
}
