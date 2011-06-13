<?php

class BannerWidget extends Widget {
	public static function define() {
		return array(
			"id" => "banner",
			"controller" => "banner",
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
	
	public function action() {
	}
}
