<?php

class User {
	private static $roleCache = array();
	
	public static function define() {
	}
	
	public static function profileByName($name) {
		return Query(<<<SQL
select *
from users
where name = ?
SQL
		, $name);
	}
	
	public static function roles($id) {
		if (isset(self::$roleCache[$id])) {
			return self::$roleCache[$id];
		}
		
		self::$roleCache[$id] = WoolDb::fetchCol(<<<SQL
select *
from access_roles_users
where userId = ?
SQL
		, $id);
		
		return self::$roleCache[$id];
	}
	
	public static function hasRole($userId, $roleId) {
		return in_array($roleId, self::roles($userId));
	}
}
