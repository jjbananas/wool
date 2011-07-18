<?php

require_once('Wool/Common/ImageCompositor.php');
require_once('Wool/App/Theme/Theme.php');

class ThemeController extends AppController {
	function images() {
		$matches = array();
		$regex = "/theme\/(.+)$/";
		preg_match($regex, Request::uri(), $matches);

		if (!isset($matches[1])) {
			return;
		}

		$image = $matches[1];
		$file = basePath('/var/burn/variables.php');
		$config = dirname($image) . "/" . fileNameOnly($image) . ".yml";
		
		$vars = array();

		if (file_exists($file)) {
			$vars = require($file);
		}

		$creator = new ImageCompositor(privatePath("/theme/" . $config), array("variables"=>$vars));

		$this->stopRender();
	}

	function adminIndex() {
		$this->def = Spyc::YAMLLoad(basePath("/theme/shaded.yml"));
		$this->params = ThemeParam::forTheme(1)->rowSet();
		
		if (Request::isPost()) {
			foreach ($this->def["params"] as $name=>$def) {
				$value = param($name, $this->params->valueBy("reference", $name, "value"));

				if ($def["type"] == "file") {
					if (!isset($_FILES[$name])) {
						continue;
					}

					$file = $_FILES[$name];
					$dest = publicPath("/theme/shaded/") . $file["name"];

					if (!copy($file["tmp_name"], $dest)) {
						continue;
					}

					$value = publicUri("/theme/shaded/") . $file["name"];
				}

				WoolDb::upsert("theme_param", array(
					"themeId" => 1,
					"reference" => $name,
					"value" => $value
				));
			}
			
			Theme::exportToBurn(1);

			$this->redirectTo(Request::uri());
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
