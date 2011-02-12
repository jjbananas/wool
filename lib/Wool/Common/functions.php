<?php

function redirectTo($url) {
	header(sprintf('Location: %1$s', $url));
	exit;
}

// Replacment for $_REQUEST[] which is: shorter, checkes for existance, and
// has no square brackets.
function param($name, $default=null) {
	if (!is_array($name)) {
		$name = array($name);
	}
	
	$param = $default;
	$use = array();
	if (isset($GLOBALS['ROUTE_PARAMS'][$name[0]])) {
		$use = $GLOBALS['ROUTE_PARAMS'];
	}
	else if (isset($_POST[$name[0]])) {
		$use = $_POST;
	}
	else if (isset($_GET[$name[0]])) {
		$use = $_GET;
	}
	
	foreach ($name as $n) {
		if (!isset($use[$n])) {
			return $default;
		}
		
		$use = $use[$n];
	}

	return $use;
}

function id_param($name, $default=null) {
	$value = param($name);
	return is_numeric($value) ? $value : $default;
}

/**
* Truncate a non-html formatted string with a suffix if its character length is longer than a given number.
* @param string $str A non-html formatted string you wish to truncate.
* @param int $length Length of the string to truncate to. The default length is 10 characters.
* @param string $suffix A trailing string to be appended at the end if the string is longer than the length parameter. Default suffix is '...'
* @return string Returns the original string if smaller, or truncated string if larger than the length parameter.
*/
function truncate($str, $length=10, $suffix='...'){
	$length -= strlen($suffix);
	if(strlen($str) > $length){
		return substr($str,0,$length) . $suffix;
	} else {
		return $str;
	}
}

/**
* Short for coalesce. 
*
* Works the same as SQL coalesce, or the || (default)
* operator in Javascript. You can actually use this as an a 'param'
* replacement on any array access.
*
* @param mixed $a
* @param mixed $b
* @return mixed Returns argument $a if set or $b if not.
*/
function coal(&$a,$b) {
	return $a ? $a : $b;
}

/**
* Works the same as the && (guard) operator in Javascript. 
*
* @param mixed $a
* @param mixed $b
* @return mixed Returns argument $a if not set or $b otherwise.
*/
function guard(&$a, $b) {
	return !$a ? $a : $b;
}

/**
* Use rather than echo for debugging purposes, since they are easier to find
* and remove. debug() only works if DEVELOPER is set to true in the config. 
*
* @param mixed $obj Anything you wish to check.
* @param mixed $exit If set to true will exit the application after output.
* @return void Nothing returned.
*/
function debug($obj, $exit=null){
	if(DEVELOPER){
		echo "<pre>";
		if (is_bool($obj)) {
			echo $obj ? 'true' : 'false';
		} else {
			var_dump($obj);
		}
		echo "</pre>";
		if(!is_null($exit)){
			exit;
		}
	}
}

// Return a flat array containing a single field from each object in an
// existing array.
function pluck($objs, $column) {
	$array = array();
	foreach ($objs as $obj) {
		$array[] = $obj->$column;
	}
	return $array;
}

function is_array_like(&$obj) {
	return is_array($obj) || $obj instanceof ArrayAccess;
}

// Much like array_sum combined with pluck. Looks up an index for each item
// in the outer array and sums those values.
function arraySumInner($a, $index) {
	$sum = 0;
	foreach ($a as $x) {
		if (is_array($x) && isset($x[$index])) {
			$sum += $x[$index];
		}
		else if (isset($x->$index)) {
			$sum += $x->$index;
		}
	}
	return $sum;
}

function array_avg($a) {
	return array_sum($a) / count($a);
}

function arrayAvgInner($a, $index) {
	return arraySumInner($a, $index) / count($a);
}

function toQueryString($a) {
	return http_build_query($a);
}

// Output an array (eg. of query string parameters) into hidden form fields.
function toHiddenForm($a) {
	$html = '';
	foreach ($a as $key=>$value) {
		$html .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\" />\n";
	}
	return $html;
}

function toHiddenFormGet($merge=array()) {
	return toHiddenForm(array_merge($_GET, $merge));
}	

// Match item can be used to ensure that a value is one of a fixed list of options.
function matchItem($item, $options, $default=null) {
	return in_array($item, $options) ? $item : coal($default, array_shift($options));
}

// Exactly the same as above but matches array indexes not values.
function matchIndex($index, $options, $default=null) {
	return isset($options[$index]) ? $index : coal($default, array_shift(array_keys($options)));
}
