<?php

/*
	Details about the current HTTP request.
*/
class Request {
	private static $method;
	private static $methodReal;
	private static $path;
	
	public static function startUp () {
		self::methods();
		self::$path = self::getHandler();
	}

	// Full URI of the current page. Pass in an array of query string parameters
	// which override the current ones, which is useful for creating links.
	// Nulling parameters will remove them from the query string:
	//
	// eg. Request:uri(array('action'=>'edit', 'id'=>null));
	//
	public static function uri($arr = array()) {
		$qs = http_build_query(array_merge($_GET, $arr));
		$qs = guard($qs, '?' . $qs);
		return self::$path . $qs;
	}
	
	public static function uriForDirect($arr = array()) {
		return base64_encode(self::uri($arr));
	}
	
	// Path is simply the URI without query string.
	public static function path($baseRelative=false) {
		return $baseRelative ? str_replace($GLOBALS['BASE_URI'], '', self::$path) : self::$path;
	}
	
	// Script is the first PHP script to be executed as part of the current
	// request. Often this is not very useful, especially in the presence of
	// mod_rewrite.
	public static function script() {
		return $_SERVER['SCRIPT_NAME'];
	}

	// The query string, optionally as an array rather than string.
	public static function query($asArray = false) {
		return ($asArray ? $_GET : $_SERVER['QUERY_STRING']);
	}

	public static function userAgent() {
		return coal($_SERVER['HTTP_USER_AGENT'], 'No user agent supplied');
	}

	public static function ipAddress() {
		return $_SERVER['REMOTE_ADDR'];
	}

	public static function host() {
		return coal($_SERVER['HTTP_HOST'], 'nohost');
	}
	
	public static function back() {
		return coal($_SERVER['HTTP_REFERER'], 'javascript:history.back(1);');
	}
	
	// Request methods. Fake methods coming with a POST request are accepted for
	// REST interface.
	public static function method($real = false) {
		return ($real ? self::$methodReal : self::$method);
	}
	public static function isGet() { return self::$method == 'GET'; }
	public static function isPost() { return self::$method == 'POST'; }
	public static function isPut() { return self::$method == 'PUT'; }
	public static function isDelete() { return self::$method == 'DELETE'; }

	public static function isAjax() {
		return (coal($_SERVER['HTTP_X_REQUESTED_WITH'], '') == 'XMLHttpRequest');
	}
	
	private static function methods() {
		self::$methodReal = $_SERVER['REQUEST_METHOD'];
		
		if (self::$methodReal != 'POST' || !isset($_REQUEST['_method'])) {
			self::$method = self::$methodReal;
			return;
		}
		
		self::$method = $_REQUEST['_method'];
	}
	
	private static function getHandler() {
		$uri = explode('?', $_SERVER['REQUEST_URI']);
		return html($uri[0]);
	}
}

Request::startUp();
