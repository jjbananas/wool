<?php

function keyColumnDisplay($item, $column, $ref) {
	if (!$item->$column) {
		return 'None Selected';
	}
	
	$title = $ref."_title";
	$val = isset($item->$title) ? $item->$title : null;
	return $val ? "#{$item->$column}: {$val}" : "#{$item->$column}";
}
