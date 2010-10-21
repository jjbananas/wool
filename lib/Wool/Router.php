<?php

require_once('Wool/Request.php');
require_once('Wool/Framework/Controller.php');
require_once('controllers/app.php');

class Route {
	const PORTAL_LEADING_PATH = 1;
	const PORTAL_SUBDOMAIN = 2;
	
	public static $routes = array();
	public static $portals = array("default");
	public static $portalLoc = self::PORTAL_LEADING_PATH;
	public static $controllers = array();
	public static $defaultController = "article";
	public static $defaultAction = "index";
	
	public static $revPortals;
	public static $revControllers;
	
	public static function add($route, $options=array()) {
		self::$routes[] = array(
			"route"=>$route,
			"options" => $options
		);
	}
	
	public static function portal($portals, $location) {
		self::$portals = $portals;
		self::$portalLoc = $location;
	}
	
	public static function controller($controllers) {
		self::$controllers = $controllers;
	}
	
	public static function many() {
		
	}
	
	public static function one() {
	}
}

require_once('config/routes.php');

class Router {
	private $portal = null;
	private $controller = null;
	private $action = null;
	
	public function __construct() {
		$this->build();
	}
	
	private function build() {
		foreach (Route::$routes as &$route) {
			$parts = trim($route['route'], '/');
			$parts = explode('/', $parts);
			
			$route['component'] = array();
			
			foreach ($parts as $part) {
				if (isset($part[0]) && $part[0] == '@') {
					$route['component'][substr($part,1)] = true;
				}
			}
		}
		
		Route::$revPortals = array_flip(Route::$portals);
		Route::$revControllers = array_flip(Route::$controllers);
	}
	
	public function dispatch() {
		$route = $this->route(Request::path(true));
		
		$this->portal = $route["portal"];
		$this->controller = $route["controller"];
		$this->action = $route["action"];
		
		$this->attemptRoute($this->portal, $this->controller, $this->action);
	}
	
	public function route($uri) {
		$path = trim($uri, '/');
		$path = explode('/', $path);
		
		foreach (Route::$routes as $route) {
			$parts = trim($route['route'], '/');
			$parts = explode('/', $parts);
			$GLOBALS['ROUTE_PARAMS'] = array();
			
			$res = $this->pathMatch($path, $parts, $route['options']);
			
			if ($res) {
				return $res['route'];
			}
		}
		
		trigger_error("Routing error '{$uri}'", E_USER_ERROR);
	}
	
	private function pathMatch($paths, $parts, $options) {
		$route = array(
			'portal'=> coal($options['portal'], reset(Route::$portals)),
			'controller'=> coal($options['controller'], Route::$defaultController),
			'action'=> coal($options['action'], Route::$defaultAction)
		);
		$params = array();
		
		$path = array_shift($paths);
		$part = array_shift($parts);
		
		while (true) {
			if (!$part) {
				// Reached the end. Success
				break;
			}
			
			if (!$path && $part) {
				// No match. Reached the end of URI before match is made.
				return false;
			}
			
			if ($part[0] === '@') {
				$type = substr($part, 1);
				$validFunc = $type . "Valid";
				$lookupFunc = $type . "Lookup";
				$name = $this->$lookupFunc($path);
				if ($this->$validFunc($name)) {
					$route[$type] = $name;
				} else {
					$part = array_shift($parts);
					continue;
				}
			}
			else if ($part[0] === ':') {
				$GLOBALS['ROUTE_PARAMS'][substr($part, 1)] = $path;
			}
			else {
				if ($path !== $part) {
					return false;
				}
			}
			
			$path = array_shift($paths);
			$part = array_shift($parts);
		}
		
		if (!$this->attemptRoute($route['portal'], $route['controller'], $route['action'], true)) {
			return false;
		}
		
		return array('route'=>$route, 'params'=>$params);
	}
	
	private function portalLookup($portal) {
		return isset(Route::$portals[$portal]) ? Route::$portals[$portal] : $portal;
	}
	private function portalRevLookup($portal) {
		return isset(Route::$revPortals[$portal]) ? Route::$revPortals[$portal] : $portal;
	}
	
	private function controllerLookup($controller) {
		return isset(Route::$controllers[$controller]) ? Route::$controllers[$controller] : $controller;
	}
	private function controllerRevLookup($controller) {
		return isset(Route::$revControllers[$controller]) ? Route::$revControllers[$controller] : $controller;
	}
	
	private function actionLookup($action) {
		return $action;
	}
	private function actionRevLookup($action) {
		return $action;
	}
	
	private function portalValid($portal) {
		return in_array($portal, Route::$portals);
	}
	
	private function controllerValid($controller) {
		return file_exists($GLOBALS['BASE_PATH'] . "/controllers/{$controller}.php");
	}
	
	private function actionValid() {
		return true;
	}
	
	public function revRoute($options) {
		$portal = coal($options['portal'], $this->portal);
		$controller = coal($options['controller'], $this->controller);
		$action = coal($options['action'], $this->action);
		
		foreach (Route::$routes as $route) {
			$defPortal = coal($route['options']['portal'], reset(Route::$portals));
			$defController = coal($route['options']['controller'], Route::$defaultController);
			$defAction = coal($route['options']['action'], Route::$defaultAction);
			
			if (
				(isset($route['component']['portal']) || $portal == $defPortal)
				&& (isset($route['component']['controller']) || $controller == $defController)
				&& (isset($route['component']['action']) || $action == $defAction)
			)
			{
				$link = array();
				$parts = trim($route['route'], '/');
				$parts = explode('/', $parts);
				$used = $route['options'];
				
				foreach ($parts as $part) {
					if ($part[0] === '@') {
						$type = substr($part, 1);
						$defType = "def" . ucwords($type);
						$lookupFunc = $type . "RevLookup";
						$used[$type] = true;
						if ($$type == $$defType) {
							continue;
						}
						$link[] = $this->$lookupFunc($$type);
					}
					else if ($part[0] === ':') {
						$param = substr($part, 1);
						if (isset($options[$param])) {
							$link[] = $options[$param];
						}
						$used[$param] = true;
					}
					else {
						$link[] = $part;
					}
				}
				
				// Build any remaining query string.
				$qs = array();
				
				foreach ($options as $option=>$value) {
					if (!isset($used[$option])) {
						$qs[$option] = $value;
					}
				}
				
				$qs = $qs ? '?' . http_build_query($qs) : '';
				
				return join("/", $link) . $qs;
			}
		}
		
		trigger_error("Route lookup failed, missing action. [{$portal}, {$controller}, {$action}]");
	}
	
	private function attemptRoute($portal, $controller, $action, $test=false) {
		$file = $GLOBALS['BASE_PATH'] . "/controllers/{$controller}.php";
		
		if (!file_exists($file)) {
			return false;
		}
		
		include_once($file);
		
		$conName = ucwords($controller) . "Controller";
		$con = new $conName;
		return $con->dispatch($portal, $controller, $action, $test);
	}
}
