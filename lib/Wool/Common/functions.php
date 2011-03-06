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

function truncate($str, $length=10, $suffix='...'){
	$length -= strlen($suffix);
	if(strlen($str) > $length){
		return substr($str,0,$length) . $suffix;
	} else {
		return $str;
	}
}

function coal(&$a,$b) {
	return $a ? $a : $b;
}

function guard(&$a, $b) {
	return !$a ? $a : $b;
}

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

// Insert a string into another at a given offset.
function str_insert($insert, $into, $offset) {
   return substr($into, 0, $offset) . $insert . substr($into, $offset);
}

// Converts an array of strings to a single camel case string.
function camelCase($arr, $pascal=false) {
	$s = $pascal ? 0 : 1;
	for($i = $s; $i < count($arr); $i++) {
			$arr[$i] = ucfirst($arr[$i]);
	}
	return implode('', $arr);
}
