<?php

class ContentWidget extends Widget {
	public static function define() {
		return array(
			"id" => "content",
			"controller" => "content",
			"name" => "Content",
			"views" => array("default"),
			"params" => array(
				"content" => array(
					"name" => "Content",
					"default" => "0"
				)
			)
		);
	}
	
	public function action() {
		$this->content = WoolTable::fetch("page_content", $this->param('content', 0));
	}

	public function configure() {
		if (Request::isPost()) {
			
		}
	}
}
