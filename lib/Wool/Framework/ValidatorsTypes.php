<?php

// Null validator which always passes.
class NullValidator extends Validator {
	public static function validate($value, $params=array()) {
		return true;
	}
}

class IntValidator extends Validator {
	public static function validate($value, $params=array()) {
		return is_numeric($value) && (!$params['unsigned'] || ($params['unsigned'] && $value >= 0));
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must be a numeric value.";
	}
	
	public static function liveValidation($params) {
		return array("digits"=>true);
	}
}

class StringValidator extends Validator {
	public static function validate($value, $params=array()) {
		return is_string($value);
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must be text.";
	}
}

class DateValidator extends Validator {
	public static function validate($value, $params=array()) {
		return true;
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} is an invalid date.";
	}
}

class DatetimeValidator extends Validator {
	public static function validate($value, $params=array()) {
		return true;
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} is an invalid datetime.";
	}
}

class EnumValidator extends Validator {
	public static function validate($value, $params=array()) {
		debug($value);
		debug($params);
		
		return in_array($value, $params['length']);
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} is not a valid choice.";
	}
}


WoolValidation::registerValidator("int", "IntValidator");
WoolValidation::registerValidator("string", "StringValidator");
WoolValidation::registerValidator("date", "DateValidator");
WoolValidation::registerValidator("datetime", "DatetimeValidator");
WoolValidation::registerValidator("enum", "EnumValidator");
