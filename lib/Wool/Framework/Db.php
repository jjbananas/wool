<?php

require_once('spyc/spyc.php');
require_once('Zend/Db.php');
require_once('Zend/Db/Adapter/Pdo/Mysql.php');
require_once('Wool/Framework/Db/WoolTable.php');
require_once('Wool/Framework/Db/SqlParser.php');
require_once('Wool/Framework/Db/Sql.php');
require_once('Wool/Framework/Db/SqlMeta.php');
require_once('Wool/Framework/Db/TransactionRaii.php');
require_once('Wool/Framework/Db/RowSet.php');
require_once('Wool/Framework/validation.php');

class WoolDb {
	private static $db;
	
	public static function connect() {
		self::$db = new Zend_Db_Adapter_Pdo_Mysql(array(
				'host'     => $GLOBALS['DB_HOST'],
				'username' => $GLOBALS['DB_USERNAME'],
				'password' => $GLOBALS['DB_PASSWORD'],
				'dbname'   => $GLOBALS['DB_NAME'],
				'profiler' => DEVELOPER
		));

		self::$db->setFetchMode(Zend_Db::FETCH_OBJ);
	}
	
	// Join across allows us to easily build paramterized queries acting on an
	// array of values.
	public static function joinAcross($sql, $params) {
		$rp = array();
		
		if (!is_array($params)) {
			return array('sql'=>$sql, 'params'=>$params);
		}
		
		foreach ($params as $name=>$param) {
			if (!is_array($param)) {
				$rp[] = $param;
				continue;
			}
			
			$count = count($param);
			$count = $count > 0 ? $count-1 : 0;
			$sql = str_replace(':' . $name, '(?' . str_repeat(',?', $count) . ')', $sql);
			
			if (count($param)) {
				foreach ($param as $p) {
					$rp[] = $p;
				}
			} else {
				$rp[] = null;
			}
		}
		
		return array('sql'=>$sql, 'params'=>$rp);
	}
	
	public static function fetchRow($sql) {
		$args = func_get_args();
		$row = call_user_func_array(array('self', 'fetchRowStrict'), $args);
		if (!$row) {
			return WoolTable::blankFromSql($sql);
		}
		return $row;
	}
	
	public static function fetchRowStrict($sql) {
		$args = func_get_args();
		$row = call_user_func_array(array(self::$db, 'fetchRow'), $args);
		if (!$row) {
			return false;
		}
		WoolTable::setQueryMeta($row, new SqlMeta($sql));
		return $row;
	}
	
	// Overload static calls and dispatch them off to the real zend database.
	public static function __callStatic($name, $args) {
		if (method_exists(self::$db, $name)) {
			return call_user_func_array(array(self::$db, $name), $args);
		}
		
		trigger_error("Call to undefined method WoolDb::{$name}", E_USER_ERROR);
	}
	
	/*
		All of the follwoing do nothing except pass through the call to Zend.
		__callStatic would work in their place, but only on PHP 5.3+
	*/
	public static function query(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'query'), $args);
	}
	public static function exec(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'exec'), $args);
	}
	public static function delete(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'delete'), $args);
	}
	public static function insert(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'insert'), $args);
	}
	public static function update(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'update'), $args);
	}
	public static function quote(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'quote'), $args);
	}
	public static function lastInsertId(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'lastInsertId'), $args);
	}
	public static function fetchOne(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'fetchOne'), $args);
	}
	public static function fetchAll(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'fetchAll'), $args);
	}
	public static function fetchCol(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'fetchCol'), $args);
	}
	public static function getProfiler(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'getProfiler'), $args);
	}
	public static function beginTransaction(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'beginTransaction'), $args);
	}
	public static function commit(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'commit'), $args);
	}
	public static function rollback(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$db, 'rollback'), $args);
	}

}

WoolDb::connect();
