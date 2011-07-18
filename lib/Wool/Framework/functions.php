<?php

function routeUri($subject){
	global $router;
	
	if(is_array($subject)){
		return baseUri('/' . Application::revRoute($subject));
	}
	
	return $subject;
}


function html($html) {
	return htmlspecialchars($html);
}

function baseUri($path) {
	return $GLOBALS['BASE_URI'] . $path;
}

function basePath($path) {
	return $GLOBALS['BASE_PATH'] . $path;
}

function publicPath($path) {
	return $GLOBALS['BASE_PATH'] . '/public' . $path;
}

function publicUri($path) {
	// Actually the same due to rewrite rules.
	return baseUri($path);
}

function privatePath($path) {
	return $GLOBALS['BASE_PATH'] . '/private' . $path;
}

function varPath($path) {
	return $GLOBALS['BASE_PATH'] . '/var' . $path;
}

// Will force the switch to HTTPS if enabled.
function secureBaseUri($path) {
	$host = coal($_SERVER['HTTP_HOST'], 'localhost');
	return (USE_SSL ? 'https://' : 'http://') . $host . $GLOBALS['BASE_URI'] . $path;
}
