<?php

function navFirst($pager) {
	if ($pager->page() <= 1) {
		return "First";
	}
	
	return '<a href="' . Request::uri(array($pager->name() . "_page" => 1)) . '">First</a>';
}

function navPrev($pager, $attr=null) {
	if ($pager->page() <= 1) {
		return "";
	}
	
	return sprintf(
		'<a href="%s" %s>%s</a>',
		Request::uri(array($pager->name() . "_page" => $pager->page()-1)),
		$attr,
		'Previous'
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
		return "Last";
	}
	
	return sprintf(
		'<a href="%s" %s>%s</a>',
		Request::uri(array($pager->name() . "_page" => $pager->totalPages())),
		$attr,
		'Last'
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
