<?php

abstract class Validator {
	// Called to validate a field with the $value provided.
	public static function validate($value, $params=array()) {}
	
	// Called to format the error message for the invalid field. $field is column
	// name. $pretty is the same but in human readable format. $value is the
	// same value passed to validate. $valParams are the parameters passed to the
	// validator definition.
	public static function errorMessage($field, $pretty, $value, $valParams) {}
	
	// This function is called to format a custom string provided per-column
	// rather than the usual per-validator.
	// $str can be a format string accepted by sprintf, and $field, $pretty, and
	// $value are passed to sptrinf in that order. It should be possible to
	// hard-code anything that is normally in $params into $str itself. Anything
	// more advanced, just create your own validator (you can even sub-class an
	// existing one).
	public static function formatErrorMessage($str, $field, $pretty, $value) {
		return sprintf($str, $field, $pretty, $value);
	}
}

class UniqueValidator extends Validator {
	public static function validate($value, $params=array()) {
		return WoolDb::fetchOne("select count(*) from {$params['table']} where {$params['column']}=?", $value) == 0;
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} in not unique, please choose another.";
	}
}
class RangeValidator extends Validator {
	// Defaults to accept anything greater than 1 so that it can easily be used
	// for primary keys. 
	public static function validate($value, $params=array()) {
		$min = coal($params['min'], 1);
		$max = coal($params['max'], null);
		
		if (!is_null($min) && $value < $min) {
			return false;
		}
		if (!is_null($max) && $value > $max) {
			return false;
		}
		
		return true;
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		$min = coal($valParams['min'], 1);
		$max = coal($valParams['max'], null);
		
		if (!is_null($min) && $value < $min) {
			return "{$pretty} must be larger than {$min}, you entered {$value}";
		}
		if (!is_null($max) && $value > $max) {
			return "{$pretty} must be less than {$max}, you entered {$value}";
		}
	}
}
class StrLenValidator extends Validator {
	// For minimum lenths only. Use the actual database lenth for maximum.
	// Defaults to accept anything greater than 1 since an empty string would be
	// accepted anyway.
	public static function validate($value, $params=array()) {
		$min = coal($params['min'], 1);
		return strlen($value) >= $min;
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		$min = coal($params['min'], 1);
		return "{$pretty} must be {$min} characters or longer.";
	}
}
class LengthValidator extends Validator {
	public static function validate($value, $params=array()) {
		if (!isset($params['length'])) { return true; }
		return strlen($value) <= $params['length'];
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must be less than {$valParams['length']} characters long.";
	}
}


WoolValidation::registerValidator("unique", "UniqueValidator");
WoolValidation::registerValidator("required", "StrLenValidator");
WoolValidation::registerValidator("range", "RangeValidator");
WoolValidation::registerValidator("minlen", "StrLenValidator");
WoolValidation::registerValidator("length", "LengthValidator");
