<?php

// MySQL type information.
class SqlTypes {
	private static $types = array(
		"binary" => array(
			"text" => false
		),
		
		"varchar" => array(
			"text" => true
		),
		
		"int" => array(
			"text" => false
		),
		
		"decimal" => array(
			"text" => false
		),
		
		"datetime" => array(
			"text" => false
		),
		
		"enum" => array(
			"text" => false
		),
		
		"float" => array(
			"text" => false
		)
	);
	
	public function isValidDataType($type) {
		return isset(self::$types[$type]);
	}
	
	public function isText($type) {
		return self::$types[$type]["text"];
	}
}
