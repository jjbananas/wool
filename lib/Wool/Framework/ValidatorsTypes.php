<?php

// Null validator which always passes.
class NullValidator extends Validator {
	public static function validate($value, $params=array()) {
		return true;
	}
}

class BoolValidator extends Validator {
	public static function validate($value, $params=array()) {
		return (
			$value == 1 || $value == "true" || $value == true ||
			$value == 0 || $value == "false" || $value == false
		);
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must be a true or false.";
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

class DecimalValidator extends Validator {
	public static function validate($value, $params=array()) {
		return is_numeric($value) && (!$params['unsigned'] || ($params['unsigned'] && $value >= 0));
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must be a decimal number.";
	}
	
	public static function liveValidation($params) {
		return array("number"=>true);
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
		return in_array($value, $params['length']);
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} is not a valid choice.";
	}
}


WoolValidation::registerValidator("null", "NullValidator");
WoolValidation::registerValidator("int", "IntValidator");
WoolValidation::registerValidator("decimal", "DecimalValidator");
WoolValidation::registerValidator("bool", "BoolValidator");
WoolValidation::registerValidator("string", "StringValidator");
WoolValidation::registerValidator("date", "DateValidator");
WoolValidation::registerValidator("datetime", "DatetimeValidator");
WoolValidation::registerValidator("enum", "EnumValidator");
