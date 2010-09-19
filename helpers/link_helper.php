<?php

// Accepts URLs as plain, MVC array, or tilde pre-pended.
function linkTo($inner, $url, $attrs='') {
	return sprintf('<a href="%s" %s>%s</a>', routeUri($url), $attrs, $inner);
}

function activeRoute($controller, $name, $action=null) {
	return ($controller->controller == $name && (!$action || $controller->action == $action)) ? 'class="active"' : '';
}
function activeUri($uri, $exact=false) {
	$match = $exact ? $uri == Request::path(true) : strncmp($uri, Request::path(true), strlen($uri)) == 0;
	return $match ? 'class="active"' : '';
}
