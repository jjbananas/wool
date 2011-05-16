<?php

function navFirst($pager, $attr=null) {
	if ($pager->page() <= 1) {
		return "";
	}
	
	return sprintf(
		'<a href="%s" %s>%s</a>',
		Request::uri(array($pager->name() . "_page" => 1)),
		$attr,
		'First'
	);
}

function navPrev($pager, $attr=null) {
	if ($pager->page() <= 1) {
		return "";
	}
	
	return sprintf(
		'<a href="%s" %s>%s</a>',
		Request::uri(array($pager->name() . "_page" => $pager->page()-1)),
		$attr,
		'Prev'
	);
}

function navNext($pager, $attr=null) {
	if ($pager->page() >= $pager->totalPages()) {
		return "";
	}
	
	return sprintf(
		'<a href="%s" %s>%s</a>',
		Request::uri(array($pager->name() . "_page" => $pager->page()+1)),
		$attr,
		'Next'
	);
}

function navLast($pager, $attr=null) {
	if ($pager->page() >= $pager->totalPages()) {
		return "";
	}
	
	return sprintf(
		'<a href="%s" %s>%s</a>',
		Request::uri(array($pager->name() . "_page" => $pager->totalPages())),
		$attr,
		'Last'
	);
}

function navPageLinks($pager, $max=10) {
	if ($max > $pager->totalPages()) {
		$max = $pager->totalPages();
	}
	
	$shift = floor($max/2);
	$page = $pager->page();
	$start = $page - $shift;
	if ($start < 1) {
		$start = 1;
	}
	$end = $start + $max - 1;
	if ($end > $pager->totalPages()) {
		$end = $pager->totalPages();
		$start = $end - $max - 1;
	}
	
	return range($start, $end);
}

function navPageLink($pager, $page, $attr=null) {
	if ($pager->page() == $page) {
		$attr = 'class="btnLink btnLinkLight"';
		return sprintf("<span %s>%s</span>", $attr, $page);
	}
	
	return sprintf(
		'<a href="%s" %s>%s</a>',
		Request::uri(array($pager->name() . "_page" => $page)),
		$attr,
		$page
	);
}

function navPerPageOptions($pager) {
	$options = array(10,20,50,100);
	$optionHTML = '';
	foreach ($options as $option) {
		$select = ($option == param($pager->name() . "_perPage") ? ' selected="selected"' : '');
		$optionHTML .= "<option value=\"{$option}\"{$select}>{$option}</option>";
	}
	return $optionHTML;
}

function gridHeaderClass($table, $column, $sortColumns) {
	$cls = array();

	if (Schema::columnEditable($table, $column)) {
		$cls[] = "editable";
	}
	
	if (isset($sortColumns[$column])) {
		$cls[] = $sortColumns[$column];
	}
	
	return join(' ', $cls);
}
