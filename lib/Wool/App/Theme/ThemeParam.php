<?php

class ThemeParam extends WoolTable {
	public static function forTheme($themeId) {
		return Query(<<<SQL
select *
from theme_param
where themeId = ?
SQL
		, $themeId);
	}
}
