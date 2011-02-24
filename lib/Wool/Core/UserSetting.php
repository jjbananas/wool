<?php

class UserSetting extends WoolTable {
	private static $cache = array();
	
	public static function forUser($userId, $key) {
		if (!isset(self::$cache[$userId])) {
			self::$cache[$userId] = new RowSet(<<<SQL
select * from user_setting where userId = ?
SQL
			, $userId);
		}
		
		return self::$cache[$userId]->by($key);
	}
	
	// Get the values for the current session user.
	public function session($key) {
		if (!Session::loggedIn()) {
			return null;
		}
		
		return self::forUser(Session::user()->userId, $key);
	}
}
