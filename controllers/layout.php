<?php

class Page {
	public function __construct($controller, $uri) {
		$this->controller = $controller;
		
		$this->row = WoolDb::fetchRow("select * from page where uri = ?", $uri);
		$this->content = new RowSet("select * from page_content where pageId = ?", $this->row->pageId);
		$this->widgets = new RowSet("select * from page_widget where pageId = ?", $this->row->pageId);
		$this->widgetParams = new RowSet(
			"select * from page_widget_param where widgetId in :widgets",
			array("widgets"=>pluck($this->widgets, "widgetId"))
		);
	}
	
	public function contentFor($area) {
		if (!$this->content->by("area", $area)) {
			return '';
		}
		
		return $this->content->by("area", $area)->content;
	}
	
	public function widgetFor($area) {
		if (!$this->widgets->by("area", $area)) {
			return '';
		}
		
		$pageWidget = $this->widgets->by("area", $area);
		$className = $pageWidget->type . "Widget";
		
		if (!class_exists($className)) {
			return '';
		}
		
		$widget = new $className($this->controller, $pageWidget, $this->widgetParams->byGroup("widgetId", $pageWidget->widgetId));
		return $widget->render($pageWidget->view);
	}
	
	public function widgetJson() {
		$json = new StdClass;
		
		foreach ($this->content as $content) {
			$item = new StdClass;
			$item->type = "content";
			$item->content = $content->content;
			
			$name = $content->area;
			$json->$name = $item;
		}
		
		foreach ($this->widgets as $widget) {
			$item = new StdClass;
			$item->type = $widget->type;
			$item->view = $widget->view;
			$item->params = new StdClass;
			
			foreach ($this->widgetParams->byGroup("widgetId", $widget->widgetId) as $param) {
				$name = $param->name;
				$item->params->$name = $param->value;
			}
			
			$name = $widget->area;
			$json->$name = $item;
		}
		
		return json_encode($json);
	}
	
	// Create or replace widgets for a specific page.
	public function widgetsFromJson($json) {
		foreach ($json as $area=>$widget) {
			if ($widget["type"] == "content") {
				if ($this->content->by("area", $area)) {
					$row = $this->content->by("area", $area);
				} else {
					$row = WoolTable::blank("page_content");
				}
				
				$row->pageId = $this->row->pageId;
				$row->area = $area;
				$row->content = tidy_repair_string($widget["content"], array(
					"show-body-only" => true,
					"doctype" => "-//W3C//DTD XHTML 1.0 Transitional//EN",
					"output-xhtml" => true
				));
				
				WoolTable::save($row);
				
				continue;
			}
			
			$types = Widget::getTypes();
			
			if (!isset($types[$widget["type"]])) {
				continue;
			}
			
			$type = $types[$widget["type"]];
			
			if ($this->widgets->by("area", $area)) {
				$row = $this->widgets->by("area", $area);
			} else {
				$row = WoolTable::blank("page_widget");
			}
			
			$row->area = $area;
			$row->pageId = $this->row->pageId;
			$row->type = $type["id"];
			$row->view = matchItem($widget["view"], $type["views"]);
			
			WoolTable::save($row);
			
			$widgetParams = $this->widgetParams->byGroup("widgetId", $row->widgetId);
			$paramRows = array();
			
			foreach ($type["params"] as $paramName=>$paramValue) {
				if ($widgetParams && $widgetParams->by("name", $paramName)) {
					$paramRow = $widgetParams->by("name", $paramName);
				} else {
					$paramRow = WoolTable::blank("page_widget_param");
				}
				
				$paramRow->widgetId = $row->widgetId;
				$paramRow->name = $paramName;
				$paramRow->value = coal($widget["params"][$paramName], $paramValue["default"]);
				$paramRows[] = $paramRow;
			}
			
			WoolTable::save($paramRows);
		}
		
		// Remove any widgets not sent.
		foreach ($this->content as $content) {
			if (!isset($json[$content->area])) {
				WoolTable::delete("page_content", $content->pageContentId);
			}
		}
		
		foreach ($this->widgets as $widget) {
			if (!isset($json[$widget->area])) {
				WoolTable::delete("page_widget", $widget->widgetId);
			}
		}
	}
}

class Widget {
	protected $controller;
	protected $pageWidget;
	private $params;
	
	private static $types = array();
	
	public function __construct($controller, $pageWidget, $params) {
		$this->controller = $controller;
		$this->pageWidget = $pageWidget;
		$this->params = $params;
	}
	
	public function param($name, $default) {
		if ($this->params->by("name", $name)) {
			return $this->params->by("name", $name)->value;
		}
		
		return $default;
	}
	
	public function renderPartial($view, $vars) {
		$this->controller->renderPartial("/layout/widget", array("widgetView"=>$view, "widgetVars"=>$vars));
	}
	
	public static function defineTypes() {
		$types = array("BannerWidget", "ProductCollectionWidget");
		
		foreach ($types as $type) {
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
}

Widget::defineTypes();

class BannerWidget extends Widget {
	public static function define() {
		return array(
			"id" => "banner",
			"name" => "Banner",
			"views" => array("default"),
			"params" => array(
				"collection" => array(
					"name" => "Image Collection",
					"default" => "1"
				)
			)
		);
	}
	
	public function render() {
		return "<div>banner goes here</div>";
	}
}

class ProductCollectionWidget extends Widget {
	public static function define() {
		return array(
			"id" => "productcollection",
			"name" => "Product Collection",
			"views" => array("default"),
			"params" => array(
				"category" => array(
					"name" => "Category",
					"default" => "1"
				),
				"something" => array(
					"name" => "Something",
					"default" => "Yo"
				)
			)
		);
	}
	
	public function render() {
		$products = new RowSet(<<<SQL
select p.*
from product p
join product_in_category pic on pic.productId = p.productId
where pic.categoryId = ?
SQL
		, $this->param("category", 0));
		
		$this->renderPartial("/product/widget/{$this->pageWidget->view}", array("products"=>$products));
		
		echo $this->param('something', '');
	}
}


function processLayout($level, $layout, $layers=array()) {
	$keywords = array("width", "grid", "direction");
	$merged = new StdClass;
	$merged->children = array();
	
	foreach (get_object_vars($layout->$level) as $name=>$def) {
		if (in_array($name, $keywords)) {
			$merged->$name = $def;
			continue;
		}
		
		if ($name == "children") {
			foreach ($def as $child) {
				foreach ($layers as $layer) {
					if (property_exists($layer, $child)) {
						$merged->children[$name] = processLayout($layer->$name, $layer, array_slice($layers, 1));
						continue 2;
					} else if (property_exists($layer, "before:". $name)) {
						$innerName = "before:". $name;
						
						$before = processLayout($layer->$innerName, $layout, array_slice($layers, 1));
						
						$merged->children = $before->children;
						$merged->children[$name] = processLayout($def, $layout, $layers);
						continue 2;
					} else if (property_exists($layer, "after:". $name)) {
						$merged->children[$name] = processLayout($def, $layout, $layers);
						
						$innerName = "after:". $name;
						$after = processLayout($layer->$innerName, $layout, array_slice($layers, 1));
						
						$merged->children += $after->children;
						continue 2;
					}
				}
				
				$merged->children[$child] = processLayout($child, $layout, $layers);
			}
		}
	}
	
	return $merged;
}

class LayoutController extends Controller {
	function index() {
		$this->page = new Page($this, Request::path(true));
		$this->layoutAreas = processLayout("body", json_decode($this->page->row->layout));
		
		$this->meta("description", $this->page->row->metaDesc);
	}
	
	function adminIndex() {
		$this->page = new Page($this, '/layout');
	}
	
	function adminSetLayout() {
		$response = array();
		
		$updateData = array();
		$updateData["layout"] = param('layout');
		WoolDb::update("page", $updateData, "pageId = " . id_param('page', 0));
		
		$page = new Page($this, '/layout');
		$widgets = json_decode(param('widgets'), true);
		$page->widgetsFromJson($widgets);
		
		$this->renderJson($response);
	}
	
	function adminSetContent() {
		$page = new Page($this, '/layout');
		$widgets = param('widgets', array());
		$page->widgetsFromJson($widgets);
		
		$this->renderJson(array());
	}
}
