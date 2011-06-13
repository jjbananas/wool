<?php

require_once("Widgets/Widget.php");

class WidgetController extends Controller {
	function adminIndex() {
		$this->setLayout("widget");
	}
}
