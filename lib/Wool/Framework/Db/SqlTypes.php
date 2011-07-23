<?php

// MySQL type information.
class SqlTypes {
	private static $types = array(
		"binary" => array(
			"text" => true,
			"numeric" => false,
			"date" => false
		),
		
		"varchar" => array(
			"text" => true,
			"numeric" => false,
			"date" => false
		),
		
		"text" => array(
			"text" => true,
			"numeric" => false,
			"date" => false,
			"defaultLength" => 65535
		),
		
		"bool" => array(
			"text" => false,
			"numeric" => true,
			"date" => false
		),
		
		"tinyint" => array(
			"text" => false,
			"numeric" => true,
			"date" => false
		),
				
		"int" => array(
			"text" => false,
			"numeric" => true,
			"date" => false,
			"defaultLength" => 10
		),
		
		"decimal" => array(
			"text" => false,
			"numeric" => true,
			"date" => false
		),
		
		"datetime" => array(
			"text" => false,
			"numeric" => false,
			"date" => true
		),
		
		"enum" => array(
			"text" => false,
			"numeric" => false,
			"date" => false
		),
		
		"float" => array(
			"text" => false,
			"numeric" => true,
			"date" => false,
			"defaultLength" => 12
		)
	);
	
	public static function isValidDataType($type) {
		return isset(self::$types[$type]);
	}
	
	public static function isText($type) {
		return self::$types[$type]["text"];
	}
	
	public static function isNumeric($type) {
		return self::$types[$type]["numeric"];
	}
	
	public static function isDate($type) {
		return self::$types[$type]["date"];
	}

	public static function defaultLength($type) {
		return isset(self::$types[$type]["defaultLength"]) ? self::$types[$type]["defaultLength"] : null;
	}
}
