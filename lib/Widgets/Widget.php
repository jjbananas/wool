<?php

class Widget {
	protected $controller;
	protected $pageWidget;
	protected $definition;
	private $params;
	private $viewVars = array();
	
	private static $types = array();
	
	public function __construct($type, $controller, $pageWidget, $params) {
		$this->definition = self::$types[$type];
		$this->controller = $controller;
		$this->pageWidget = $pageWidget;
		$this->params = $params;
		
		$this->viewVars["widgetName"] = $pageWidget->area;
	}

	// Get and set to make storing view variables simpler.
	public function &__get($field) {
		if (isset($this->viewVars[$field])) {
			return $this->viewVars[$field];
		}
		
		trigger_error("Undefined view variable '{$field}'");
	}
	
	public function __set($field, $value) {
		$this->viewVars[$field] = $value;
	}
	
	public function param($name, $default) {
		if ($this->params->by("name", $name)) {
			return $this->params->by("name", $name)->value;
		}
		
		return $default;
	}
	
	public function renderPartial($view, $vars) {
		$this->controller->renderPartial("/page/widget", array("widgetView"=>$view, "widgetVars"=>$vars));
	}
	
	public static function defineTypes() {
		$files = glob(dirname(__FILE__) . "/*.php");
		
		foreach ($files as $file) {
			require_once($file);
			
			$type = basename($file, ".php");
			
			if ($type == "Widget") {
				continue;
			}
			
			$type .= "Widget";
			
			$def = call_user_func(array($type, "define"));
			self::$types[$def["id"]] = $def;
		}
	}
	
	public static function getTypes() {
		return self::$types;
	}
	
	public static function typeDefJson() {
		return json_encode(self::$types);
	}
	
	public static function widgetOptions() {
		$options = array("layout"=>"Layout", "content"=>"Content");
		
		foreach (self::$types as $type) {
			$options[$type["id"]] = $type["name"];
		}
		
		return $options;
	}
	
	public static function adminDispatch($con, $type, $pageId, $area, $action=null) {
		if (!isset(self::$types[$type])) {
			trigger_error("Missing widget type", E_USER_ERROR);
		}
		
		$className = $type . "Widget";
		$pageWidget = WoolDb::fetchRow("select * from page_widget where pageId = ? and area = ?", array($pageId, $area));
		$params = new RowSet("select * from page_widget_param where widgetId = ?", $pageWidget->widgetId);
		
		$widget = new $className($type, $con, $pageWidget, $params);
		$widget->adminRender();
	}
	
	public function submitted() {
		return param("widgetSubmit") == $this->pageWidget->area;
	}
	
	public function action() {
	}
	
	public function render() {
		$this->action();
		$this->renderPartial("/widgets/{$this->definition["controller"]}/{$this->pageWidget->view}", $this->viewVars);
	}
	
	public function adminRender() {
		$this->controller->render("/widgets/{$this->definition["controller"]}/config");
	}
}

Widget::defineTypes();

