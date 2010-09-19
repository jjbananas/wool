<?php

require_once('Shaded/Core/AccessRoleContact.php');
require_once('Shaded/Core/AccessLocation.php');

class AccessRole extends EvanceTable {
	// Common role types
	const ADMIN = 1;
	
	public static function define() {
	}
	
	public static function loginPageFor($id) {
		return EvanceDb::fetchOne(
			"select loginUrl from access_roles where accessRoleId = ?",
			$id
		);
	}
	
	public static function deniedPageFor($id) {
		return EvanceDb::fetchOne(
			"select deniedUrl from access_roles where accessRoleId = ?",
			$id
		);
	}
}

EvanceTable::registerTable("AccessRole", "access_roles");
