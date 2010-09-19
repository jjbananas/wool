<?php

require_once('Shaded/Common/Cipher.php');
require_once('Shaded/Core/User.php');

class Session extends EvanceTable {
	private static $session;
	private static $user;
	
	public static function define() {
	}
	
	public static function start() {
		session_name($GLOBALS['PROJECT_NAME'] . '_session');
		session_start();
		
		self::sessionStart();
		self::$user = EvanceTable::fetch("users", self::$session->userId);
	}
	
	public static function loggedIn() {
		return !!self::$user->userId;
	}
	
	public static function user() {
		return self::$user;
	}
	
	public static function login($user, $pass) {
		if (!$user || !$pass) {
			return false;
		}
		
		$user = EvanceDb::fetchRow(<<<SQL
select u.*
from users u
where u.email = ? and u.password = ?
limit 1
SQL
		, array($user, Cipher::blowfishEnc($pass)));
		
		if ($user->userId) {
			self::loginUser($user->userId);
			return true;
		}
		
		return false;
	}
	
	public static function loginUser($uid) {
		$sid = self::$session->sessionId;
		EvanceDb::update("sessions", array("userId"=>$uid), "sessionId = {$sid}");
	}
	
	public static function logout() {
		if (!self::loggedIn()) {
			return;
		}
		
		$sid = self::$session->sessionId;
		EvanceDb::update("sessions", array("userId"=>0), "sessionId = {$sid}");
	}
	
	private static function sessionStart() {
		$sid = session_id();
		
		self::$session = Query("select * from sessions where phpSession = ? limit 1", $sid)->fetchRow();
		if (self::$session->sessionId) {
			return;
		}
		
		self::$session->phpSession = $sid;
		self::$session->userId = 0;
		self::$session->ipAddress = coal(ip2long(Request::ipAddress()), '1');
		self::$session->token = rand(0, 999999999);
		self::$session->createdOn = now();
		
		EvanceTable::save(self::$session);
	}
}

EvanceTable::registerTable("Session", "sessions");
