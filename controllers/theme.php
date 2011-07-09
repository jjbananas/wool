<?php

require_once('Wool/Common/ImageCompositor.php');
require_once('Wool/App/Theme/Theme.php');

class ThemeController extends AppController {
	function adminIndex() {
		$this->def = Spyc::YAMLLoad(basePath("/theme/shaded.yml"));
		$this->params = ThemeParam::forTheme(1)->rowSet();
		
		if (Request::isPost()) {
			foreach ($this->def["params"] as $name=>$def) {
				WoolDb::upsert("theme_param", array(
					"themeId" => 1,
					"reference" => $name,
					"value" => param($name, $this->params->valueBy("reference", $name, "value"))
				));
			}
			
			Theme::exportToBurn(1);
		}
	}
	
	function adminPreviewImage() {
		$path = publicPath("/image-create/");
		$this->def = Spyc::YAMLLoad(basePath("/theme/shaded.yml"));
		$params = ThemeParam::forTheme(1)->rowSet();
		
		$vars = array();
		foreach ($this->def["params"] as $name=>$def) {
			$vars[$name] = param($name, $params->valueBy("reference", $name, "value"));
		}
		
		$creator = new ImageCompositor($path . "shaded.yml", array(
			"variables" => $vars
		));
		$this->stopRender();
	}
}
