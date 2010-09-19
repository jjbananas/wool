<?php

require_once('Shaded/Core/Session.php');

class AppController extends Controller {
	function startUp() {
	}
}

class DefaultPortal extends PortalController {
	public function __construct($con) {
	}
}

class AdminPortal extends PortalController {
	public function __construct($con) {
	}
}
