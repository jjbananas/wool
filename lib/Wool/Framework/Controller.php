<?php

require_once('Burn/Burn.php');
require_once('Wool/Core/AccessRole.php');

class Controller {
	private $hasRendered = false;
	private $layout = "app";
	private $viewVars = array();
	
	protected $portal = null;
	
	/*
		Dispatch methods
	*/
	public function dispatch($portal, $controller, $action, $test=false) {
		$this->controller = $controller;
		$this->action = $action;
		$this->portalName = $portal;
		
		$view = ($portal == "default" ? $action : $portal . '_' . $action);
		$actionFunc = ($portal == "default" ? $action : $portal . ucwords($action));
		
		$portalCls = ucwords($portal) . "Portal";
		if (!class_exists($portalCls)) {
			return false;
		}
		
		if (!is_callable(array($this, $actionFunc))) {
			return false;
		}
		
		if ($test) {
			return true;
		}
		
		$this->checkPermissions($portal, $controller, $action);
		
		$this->portal = new $portalCls($this);
		
		// Add default helpers.
		$this->addHelper("app");
		$this->addHelper("link");
		$this->addHelper("form");
		$this->addHelper($controller);
		
		// Do the dispatch.
		$this->startUp();
		$this->$actionFunc();
		$this->render($view);
		$this->shutDown();
		
		return true;
	}
	
	private function checkPermissions($portal, $controller, $action) {
		$allow = new RowSet(<<<SQL
select accessRoleId accessRoleId, length(al.resource) score
from access_locations al
where
	al.resource = '/{$portal}'
	or al.resource = '/{$portal}/{$controller}'
	or al.resource = '/{$portal}/{$controller}/{$action}'
order by score desc, accessRoleId desc
SQL
		);

		// No restrictions at all means anonymous access is allowed.
		if (count($allow) == 0 || $allow[0]->accessRoleId == 0) {
			return true;
		}
		
		if (!Session::loggedIn()) {
			$this->redirectTo(baseUri(AccessRole::loginPageFor($allow[0]->accessRoleId)) . "?direct=" . Request::uriForDirect());
		}
		
		// Next we need to find a match between allowed roles and the contact's
		// roles.
		$contactRoles = User::roles(Session::user()->userId);
		
		// Start with most specific matches and work outwards.
		foreach ($allow->byGroup("score") as $roles) {
			// Check for explicit anonymous access at this level.
			if ($roles->by("accessRoleId", 0)) {
				return true;
			}
			
			foreach ($roles as $role) {
				if (in_array($role->accessRoleId, $contactRoles)) {
					return true;
				}
			}
		}
		
		$this->redirectTo(baseUri(AccessRole::deniedPageFor($allow[0]->accessRoleId)));
	}
	
	public function addHelper($name) {
		if (file_exists($GLOBALS['BASE_PATH'] . '/helpers/' . $name . '_helper.php')) {
			include_once($GLOBALS['BASE_PATH'] . '/helpers/' . $name . '_helper.php');
		}
	}
	
	
	/*
		General
	*/
	
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
	
	// Add style and scripts to the page
	const MEDIA_NORMAL = 1;
	const MEDIA_TOP = 2;
	const MEDIA_HEADER = 3;
	
	private $css = array();
	private $js = array();
	private $meta = array();
	
	public function css($path, $type=self::MEDIA_NORMAL) {
		$files = Burn::expandDebugFileList($path);
		foreach ($files as $file) {
			$this->css[$type][$file] = sprintf('<link rel="stylesheet" type="text/css" href="%s" />', baseUri($file));
		}
	}
	public function js($path, $type=self::MEDIA_NORMAL) {
		$files = Burn::expandDebugFileList($path);
		foreach ($files as $file) {
			$this->js[$type][$file] = sprintf('<script src="%s"></script>', baseUri($file));
		}
	}
	public function meta($type, $content) {
		$this->meta[] = sprintf("<meta name=\"%s\" content=\"%s\" />", $type, html($content));
	}
	
	public function headerContent() {
		echo join("\n", coal($this->css[self::MEDIA_HEADER], array()));
		echo join("\n", coal($this->css[self::MEDIA_TOP], array()));
		echo join("\n", coal($this->css[self::MEDIA_NORMAL], array()));
		echo join("\n", coal($this->js[self::MEDIA_HEADER], array()));
		echo join("\n", $this->meta);
	}
	public function footerContent() {
		echo join("\n", coal($this->js[self::MEDIA_TOP], array()));
		echo join("\n", coal($this->js[self::MEDIA_NORMAL], array()));
	}
	
	public function addStandardMedia($renderedView) {
		$this->addAvailableMedia("app");
		$this->addAvailableMedia($this->portalName);
		$this->addAvailableMedia($this->controller . '/common');
		$this->addAvailableMedia($this->controller . '/' . $this->portalName);
		$this->addAvailableMedia($this->controller . '/common_' . $this->action);
		$this->addAvailableMedia($renderedView);
	}
	
	protected function addAvailableMedia($path) {
		if (file_exists(publicPath("/css/" . $path . ".css"))
			|| file_exists(publicPath("/css/" . $path . ".conf"))) 
		{
			$this->css("/css/" . $path . ".css", self::MEDIA_TOP);
		}
		
		if (file_exists(publicPath("/js/" . $path . ".js"))
			|| file_exists(publicPath("/js/" . $path . ".conf")))
		{
			$this->js("/js/" . $path . ".js", self::MEDIA_TOP);
		}
	}
	
	function viewFile($view) {
		if ($view[0] !== '/') {
			return strtolower($this->controller) . '/' . $view;
		}
		
		return substr($view, 1);
	}

	// Render a specific view. If no view is rendered the view matching the
	// action name with be automatically rendered at the end of the action.
	function render($name, $viewVars=null) {
		echo $this->renderToString($name, $viewVars, $this->layout);
	}
	
	function renderToString($renderedView, $viewVars=array(), $layout="app") {
		if ($this->hasRendered && $layout) {
			return '';
		}
		$this->hasRendered = true;
		
		$viewVars = coal($viewVars, $this->viewVars);
		$viewVars['self'] = $this;
		$renderedView = $this->viewFile($renderedView);
		
		extract($viewVars);
		
		ob_start();
		require("views/{$renderedView}.php");
		$body_content = ob_get_clean();
		
		if ($layout) {
			$this->addStandardMedia($renderedView);
			ob_start();
			require("views/layouts/{$layout}.php");
			$body_content = ob_get_clean();
		} 
		
		return $body_content;
	}

	function renderJson($obj){
		$this->hasRendered = true;
		echo json_encode($obj);
	}
	
	// Shortcut for rendering partials / Ajax.
	function renderPartial($name, $viewVars=null) {
		echo $this->renderToString($name . '_partial', $viewVars, null);
	}
	function renderPartials($name, $collection, $each, $viewVars=null) {
		$iterator = $each.'_num';
		$totalVar = $each.'_total';
		$total = count($collection);
		$num = 0;
		
		foreach ($collection as $item) {
			$viewVars[$each] = $item;
			$viewVars[$iterator] = $num;
			$viewVars[$totalVar] = $total;
			echo $this->renderToString($name . "_partial", $viewVars, null);
			$num++;
		}
	}
	
	function canRenderPartial($view) {
		$view = $this->viewFile($view);
		return file_exists_cached(basePath("/views/{$view}_partial.php"));
	}
	
	// Change the layout template.
	function setLayout($name) {
		$this->layout = $name;
	}
	
	function stopRender() {
		$this->hasRendered = true;
	}
	
	// Redirect using either a url or a controller + action.
	function redirectTo($to, $allowDirect=true) {
		if (param('direct') && $allowDirect) {
			$direct = base64_decode(param('direct'));
			redirectTo(($direct ? $direct : param('direct')));
		}

		if (is_array($to)) {
			$to = routeUri($to);
		}
		
		redirectTo($to);
	}
	
	
	/*
		Overrides
	*/
	public function startUp() {}
	public function shutDown() {}
}


class PortalController {
}
