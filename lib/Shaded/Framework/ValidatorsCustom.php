<?php

class EmailValidator extends Validator {
	public static function validate($value, $params=array()) {
		$atIndex = strrpos($value, "@");
		if (is_bool($atIndex) && !$atIndex) {
			return false;
		}
		
		$domain = substr($value, $atIndex+1);
		$local = substr($value, 0, $atIndex);
		$localLen = strlen($local);
		$domainLen = strlen($domain);
		
		if ($localLen < 1 || $localLen > 64) {
			return false;
		}
		
		if ($domainLen < 1 || $domainLen > 255) {
			return false;
		}
		
		if ($local[0] == '.' || $local[$localLen-1] == '.') {
			return false;
		}
		
		if (preg_match('/\\.\\./', $local)) {
			// Two consecutive dots.
			return false;
		}
		
		if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
			// character not valid in domain part
			return false;
		}
		
		if (preg_match('/\\.\\./', $domain)) {
			// Two consecutive dots.
			return false;
		}
		
		if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
			str_replace("\\\\","",$local)))
		{
			// character not valid in local part unless 
			// local part is quoted
			if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
				return false;
			}
		}
		
		if (!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
			// Not found in DNS
			return false;
		}
		
		return true;
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must be a valid email address.";
	}
}

class NoHtmlValidator extends Validator {
	// For minimum lenths only. Use the actual database lenth for maximum.
	// Defaults to accept anything greater than 1 since an empty string would be
	// accepted anyway.
	public static function validate($value, $params=array()) {
		return (strpos($value, '<') === false && strpos($value, '>') === false);
	}
	
	public static function errorMessage($field, $pretty, $value, $valParams) {
		return "{$pretty} must not contain HTML including '<' or '>' characters..";
	}
}


EvanceValidation::registerValidator("email", "EmailValidator");
EvanceValidation::registerValidator("nohtml", "NoHtmlValidator");
