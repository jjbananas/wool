<?php

class IntValidator extends Validator {
	public static function validate($value, $params=array()) {
		return is_numeric($value) && (!$params['unsigned'] || ($params['unsigned'] && $value >= 0));
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must be a numeric value.";
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
		return in_array($value, $params['options']);
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} is not a valid choice.";
	}
}


EvanceValidation::registerValidator("int", "IntValidator");
EvanceValidation::registerValidator("string", "StringValidator");
EvanceValidation::registerValidator("date", "DateValidator");
EvanceValidation::registerValidator("datetime", "DatetimeValidator");
EvanceValidation::registerValidator("enum", "EnumValidator");
