<?php

require_once('spyc/spyc.php');
require_once('Wool/Framework/Db/WoolTable.php');
require_once('Wool/Framework/Db/SqlParser.php');
require_once('Wool/Framework/Db/Sql.php');
require_once('Wool/Framework/Db/SqlMeta.php');
require_once('Wool/Framework/Db/Transaction.php');
require_once('Wool/Framework/Db/RowSet.php');
require_once('Wool/Framework/validation.php');

// A simple layer above PDO with some useful additions.
class WoolDb {
	private static $pdo;
	private static $fetchMode = PDO::FETCH_OBJ;
	
	private static $connections = array();
	
	public static function connect($host, $db, $user, $pass, $uniqueId="default") {
		self::$pdo = new PDO("mysql:host={$host};dbname={$db}", $user, $pass);
		self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		self::$connections[$uniqueId] = self::$pdo;
	}
	
	public static function switchConnection($uniqueId) {
		if (!isset(self::$connections[$uniqueId])) {
			trigger_error("Attempting to switch to in-active database connection: '{$uniqueId}'", E_USER_ERROR);
			return;
		}
		
		self::$pdo = self::$connections[$uniqueId];
	}
	
	//
	// Transactions
	//
	public static function beginTransaction() {
		return self::$pdo->beginTransaction();
	}
	
	public static function commit() {
		return self::$pdo->commit();
	}
	
	public static function rollBack() {
		return self::$pdo->rollBack();
	}
	
	public static function inTransaction() {
		return self::$pdo->inTransaction();
	}
	
	//
	// Error codes
	//
	public static function errorCode() {
		return self::$pdo->errorCode();
	}
	
	public static function errorInfo() {
		return self::$pdo->errorInfo();
	}
	
	//
	// Queries
	//
	
	// Issue a non-select statement to the database, return the number of affected
	// rows.
	public static function exec($statement) {
		return self::$pdo->exec($statement);
	}
	
	// Query the database and return a PDOStatement object for the result set.
	public static function query(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$pdo, 'query'), $args);
	}
	
	// Prepare a statement to be queried on the database. Call execute on the
	// resultant PDOStatement object to bind parameters and perform the query.
	public static function prepare(/*...*/) {
		$args = func_get_args();
		return call_user_func_array(array(self::$pdo, 'prepare'), $args);
	}
	
	public static function lastInsertId($name=null) {
		return self::$pdo->lastInsertId($name);
	}
	
	// If for some reason you can't use paramaterized queries, use quote to escape
	// and quote a given string.
	public static function quote($string, $paramType=PDO::PARAM_STR) {
		return self::$pdo->quote($string, $paramType);
	}
	
	public static function quoteIdentifier($name) {
		return "`{$name}`";
	}
	
	
	//
	// Higher-level query builders
	//
	
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
	
	public static function paramQuery($query, $params=array()) {
		$params = is_array($params) ? $params : array($params);
		$smnt = self::prepare($query);
		$smnt->execute($params);
		return $smnt;
	}
	
	// Insert a row given a table name and an array of column:value pairs.
	public static function insert($table, $data) {
		$table = self::quoteIdentifier($table);
		$columns = join(",", array_keys($data));
		$marks = join(",", array_fill(0, count($data), "?"));
		$values = join(",", $data);
		$query = "insert into {$table} ({$columns}) values ({$marks})";
		
		$smnt = self::paramQuery($query, array_values($data));
		$count = $smnt->rowCount();
		$smnt->closeCursor();
		
		return $count;
	}
	
	// Update a row given a table name, column:value data pairs, and column:value
	// where conditions.
	public static function update($table, $data, $where) {
		$table = self::quoteIdentifier($table);
		$params = array();
		
		$updates = array();
		foreach ($data as $column=>$value) {
			$updates[] = self::quoteIdentifier($column) . "=?";
			$params[] = $value;
		}
		$updates = join(",", $updates);
		
		$wheres = array();
		foreach ($where as $column=>$value) {
			$wheres[] = $column . "=?";
			$params[] = $value;
		}
		$wheres = join(" and ", $wheres);
		
		$query = "update {$table} set {$updates} where {$wheres}";
		
		$smnt = self::paramQuery($query, array_values($params));
		$count = $smnt->rowCount();
		$smnt->closeCursor();
		
		return $count;
	}
	
	// Delete a row given a table name and an array of column:value where
	// conditions.
	public static function delete($table, $where) {
		$table = self::quoteIdentifier($table);
		$params = array();
		
		$wheres = array();
		foreach ($data as $column=>$value) {
			$wheres[] = $column . "=?";
			$params[] = $value;
		}
		$wheres = join(" and ", $wheres);
		
		$query = "delete from {$table} where {$wheres}";
		
		$smnt = self::paramQuery($query, array_values($params));
		$count = $smnt->rowCount();
		$smnt->closeCursor();
		
		return $count;
	}
	
	// Upsert. In other words, insert if unique keys are not present otherwise
	// update the matching record.
	public static function upsert($table, $data=array()) {
		$fields = join(', ', array_keys($data));
		$marks = join(",", array_fill(0, count($data), "?"));
		
		$updates = array();
		foreach ($data as $column=>$value) {
			$updates[] = "{$column}=values({$column})";
		}
		$updates = join(",\n", $updates);
		
		$sql = <<<SQL
insert into {$table}
({$fields})
values
({$marks})
on duplicate key update
{$updates}
SQL;

		// execute the statement and return the number of affected rows
		$stmt = self::paramQuery($sql, array_values($data));
		$result = $stmt->rowCount();
		return $result;
	}
	
	
	//
	// Higher-level querying
	//
	
	// Fetch everything from a single table. Obvious performace issues apply, so
	// make sure this is what you want.
	public static function fetchTable($table, $fetchMode=null) {
		$fetchMode = $fetchMode ? $fetchMode : self::$fetchMode;
		$table = self::quoteIdentifier($table);
		
		$smnt = self::query("select * from {$table}");
		return $smnt->fetchAll($fetchMode);
	}
	
	// Fetch all results from a query.
	public static function fetchAll($query, $params=array(), $fetchMode=null) {
		$params = is_array($params) ? $params : array($params);
		$fetchMode = $fetchMode ? $fetchMode : self::$fetchMode;
		$smnt = self::paramQuery($query, $params);
		return $smnt->fetchAll($fetchMode);
	}
	
	// Fetch the first returned row in the result set.
	public static function fetchRow($query, $params=array(), $fetchMode=null) {
		$params = is_array($params) ? $params : array($params);
		$fetchMode = $fetchMode ? $fetchMode : self::$fetchMode;
		$smnt = self::paramQuery($query, $params);
		return $smnt->fetch($fetchMode);
	}
	
	// Fetch a column from the result set as an array of values.
	public static function fetchCol($query, $params=array(), $column=0) {
		$params = is_array($params) ? $params : array($params);
		$smnt = self::paramQuery($query, $params);
		return $smnt->fetchAll(PDO::FETCH_COLUMN, $column);
	}
	
	// Fetch the first returned row in the result set.
	public static function fetchPairs($query, $params=array()) {
 		$params = is_array($params) ? $params : array($params);
		$smnt = self::paramQuery($query, $params);
		
		$data = array();
		while ($row = $smnt->fetch(PDO::FETCH_NUM)) {
			$data[$row[0]] = $row[1];
		}
		return $data;
	}
	
	// Fetch a single column from the first row in the result set.
	public static function fetchOne($query, $params=array(), $column=0) {
		$params = is_array($params) ? $params : array($params);
		
 		$smnt = self::paramQuery($query, $params);
		$value = $smnt->fetchColumn($column);
		$smnt->closeCursor();
		return $value;
	}
}
