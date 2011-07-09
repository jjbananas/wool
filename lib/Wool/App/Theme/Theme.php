<?php

require_once('Wool/App/Theme/ThemeParam.php');

class Theme extends WoolTable {
	public static function exportToBurn($themeId) {
		$file = $GLOBALS['BASE_PATH'] . '/var/burn/variables.php';
		
		$exportParams = array();
		$params = ThemeParam::forTheme($themeId)->rowSet();
		
		foreach ($params as $param) {
			$exportParams[$param->reference] = $param->value;
		}

		return file_put_contents_mkdir($file, "<?php\nreturn " . var_export($exportParams, true) . ";\n");
	}
}
