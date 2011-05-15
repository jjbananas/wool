<?php

require_once('Wool/Common/ImageCompositor.php');

class ThemeController extends AppController {
	function adminIndex() {
	}
	
	function adminPreviewImage() {
		$path = publicPath("/images/theme/shaded.png");
		$path = publicPath("/image-create/");
		
		$creator = new ImageCompositor($path . "shaded.yml", array(
			"variables" => array(
				"foreground"=>param("foreground"),
				"background"=>param("background")
			)
		));
		$this->stopRender();
	}
}
