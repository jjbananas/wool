<?php

function keyColumnDisplay($item, $column, $ref) {
	if (!$item->$column) {
		return 'None Selected';
	}
	
	$title = $ref."_title";
	$val = isset($item->$title) ? $item->$title : null;
	return $val ? "#{$item->$column}: {$val}" : "#{$item->$column}";
}

function addEditHeading($table, $item) {
	$u = Schema::uniqueColumn($table);
	$name = Schema::displayName($table);

	if ($item->$u) {
		return "Edit {$name} #{$item->$u}";
	}

	return "Add {$name}";
}
