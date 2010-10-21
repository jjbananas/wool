<?php

// matchItem customised for valid SQL order directions.
function matchSqlOrder($dir, $default=null) {
	return matchItem($dir, array(
		"asc",
		"desc"
	), $default);
}

// Match the MySQL now() function.
function now() {
	return sqlDate(time());
}

function sqlDate($timestamp) {
	return date('Y-m-d H:i:s', $timestamp);
}

function sqlEmptyDate($date) {
	if ($date == "0000-00-00 00:00:00" || $date == "0000-00-00") {
		return true;
	}
	
	return false;
}
