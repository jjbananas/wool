<?php

class WoolTable {
	// The global database schema, created from the database.
	private static $schema = array();
	
	private static $additionalColumns = array();
	private static $registering = null;
	private static $registered = array();
	private static $names = array();
	private static $mergeGroups = array();
	
	private static $queryMeta = array();
	private static $pealCache = array();
	
	public static function init() {
		if (DEVELOPER && !file_exists($GLOBALS['BASE_PATH'] . '/var/database/schema.php')) {
			require_once('Wool/Db/SchemaExport.php');
			exportSchema($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
		}

		self::$schema = require($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
	}
	
	public static function define() {
	}
	
	public static function exportYaml($dir) {
		foreach (self::$schema as $name=>$table) {
			file_put_contents_mkdir($dir . $name . '.yaml', Spyc::YAMLDump($table));
		}
	}
	
	public static function validation($column, $type, $params=array()) {
		WoolValidation::add(self::$registering, $column, $type, $params);
	}
	
	public static function name($column, $pretty) {
		self::$names[self::$registering][$column] = $pretty;
	}
	
	public static function nullable(/*...*/) {
		$cols = func_get_args();
		foreach ($cols as $column) {
			self::$schema[self::$registering]["columns"][$column]['nullable'] = true;
		}
	}
	
	public static function defaultValue($column, $value) {
		self::$schema[self::$registering]["columns"][$column]['default'] = $value;
	}
	
	public static function accessible($cols, $mergeGrp="default") {
		$cols = is_array($cols) ? $cols : array($cols);
		foreach ($cols as $column) {
			self::$mergeGroups[self::$registering][$mergeGrp][$column] = true;
		}
	}
	
	public static function columnType($column, $type) {
		call_user_func(array($type . 'ColumnType', 'define'), $column);
	}
	
	public static function column($column, $default, $type, $length=null, $nullable=false) {
		if (isset(self::$schema[self::$registering]["columns"][$column])) {
			trigger_error("Attempting to override existing database column: '{$column}'", E_USER_ERROR);
		}
		
		self::$schema[self::$registering]["columns"][$column] = array (
			'name' => $column,
			'default' => $default,
			'nullable' => $nullable,
			'type' => $type,
			'length' => $length,
			'scale' => null,
			'primary' => false,
			'increment' => false,
			'additional' => true
		);
	}
	
	public static function setQueryMeta($obj, $meta) {
		$id = spl_object_hash($obj);
		self::$queryMeta[$id] = $meta;
	}
	
	public static function getQueryMeta($obj) {
		$id = spl_object_hash($obj);
		return self::$queryMeta[$id];
	}
	
	/*
		Peal off a single source table from the result set. All fields will revert
		to their original names. But any changes will be reflected in the original
		result set with the selected names.
	*/
	public static function peal($obj, $tableAlias) {
		$id = spl_object_hash($obj);
		
		if (isset(self::$pealCache[$id][$tableAlias])) {
			return self::$pealCache[$id][$tableAlias];
		}
		
		if (!isset(self::$queryMeta[$id])) {
			throw Exception("No query meta data set for this object.");
		}
		
		$meta = self::$queryMeta[$id];
		$proxy = new RowProxy($obj, $meta->columnsForTable($tableAlias));
		self::setQueryMeta($proxy, new SqlMeta($meta->realTable($tableAlias), true));
		self::$pealCache[$id][$tableAlias] = $proxy;
		return $proxy;
	}
	
	public static function validators($obj, $col) {
		$id = spl_object_hash($obj);
		$meta = self::$queryMeta[$id];
		
		$table = $meta->columnTable($col);
		$source = $meta->columnSource($col);
		
		return WoolValidation::getFor($table, $source);
	}
	
	// Validate one or many rows. Identical to the validation preformed during
	// a save.
	public static function validate($objs) {
		return self::save($objs, true, false, true);
	}
	
	private static function validateTable($tableAlias, $obj, $insert=true) {
		$id = spl_object_hash($obj);
		$meta = self::$queryMeta[$id];
		$table = $meta->realTable($tableAlias);
		$valid = true;
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			$column = $meta->columnAlias($tableAlias, $colName);
			
			if ($column && isset($obj->$column)) {
				$colVal = $obj->$column;
			} else {
				// Updates don't need all fields.
				if (!$insert) { continue; }
				
				$colVal = $col['default'];
			}
			
			// Empty fields which are nullable don't need to pass any other
			// validation. Non-empty fields always do even if they are potentially
			// nullable.
			if (is_null($colVal) && ($col['nullable'] || $col['increment'])) {
				continue;
			}
			
			// Test all attached validators.
			$valid = self::validateColumn($table, $column, $colVal, $obj);
		}
		
		// Whole object validation
		$cls = self::$registered[$table];
		if (method_exists($cls, 'validateRow')) {
			if (!call_user_func(array($cls, 'validateRow'), $obj)) {
				$valid = false;
			}
		}
		
		return $valid;
	}
	
	public static function validateColumn($table, $column, $value, $obj) {
		$pretty = isset(self::$names[$table][$column]) ? self::$names[$table][$column] : $column;
		
		return WoolValidation::validate($table, $column, $obj, $value, $pretty);
	}
	
	public static function registerTable($cls, $name) {
		self::$registered[$name] = $cls;
		self::$registering = $name;
		if (!isset(self::$schema[$name])) {
			trigger_error("Table '{$name}' not found in schema cache", E_USER_NOTICE);
			self::$schema[$name] = array();
		}
		call_user_func(array($cls, 'define'));
		self::registerTableTypeValidators();
		self::$registering = null;
	}
	
	public static function tableList() {
		return array_keys(self::$schema);
	}
	
	public static function displayName($table) {
		return coal(self::$schema[$table]["info"]["name"], $table);
	}
	
	public static function shortName($table) {
		return coal(self::$schema[$table]["info"]["shortName"], self::displayName($table));
	}
	
	public static function uniqueColumn($table) {
		return self::tableAutoIncrement($table);
	}
	
	public static function description($table) {
		return coal(self::$schema[$table]["info"]["description"], "");
	}
	
	public static function columns($table) {
		return array_keys(self::$schema[$table]["columns"]);
	}
	
	public static function editableColumns($table) {
		$cols = array();
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if (self::columnEditable($table, $colName)) {
				$cols[$colName] = $col;
			}
		}
		
		return $cols;
	}
	
	public static function columnEditable($table, $column) {
		$col = self::$schema[$table]["columns"][$column];
		
		if ($col['increment']) {
			return false;
		}
		
		if ($col['derived']) {
			return false;
		}
		
		return true;
	}
	
	public static function getColumnType($table, $column) {
		return self::$schema[$table]["columns"][$column]["type"];
	}
	
	public static function columnName($table, $column) {
		return coal(self::$schema[$table]["columns"][$column]["name"], $column);
	}
	
	public static function derivedColumns($table) {
		$cols = array();
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if (self::isColumnDerived($table, $colName)) {
				$cols[$colName] = $col;
			}
		}
		
		return $cols;
	}
	
	public static function isColumnDerived($table, $column) {
		return self::$schema[$table]["columns"][$column]["derived"];
	}
	
	public static function columnIsKey($table, $column) {
		if (!isset(self::$schema[$table]["keys"])) {
			return false;
		}
		
		foreach (self::$schema[$table]["keys"] as $key) {
			if (isset($key["columns"][$column])) {
				return true;
			}
		}
		
		return false;
	}
	
	public static function allColumns($table) {
		return self::$schema[$table]["columns"];
	}
	
	private static function registerTableTypeValidators() {
		$typeParams = array(
			'int' => array('length'=>10),
			'tinyint' => array('length'=>3),
			'smallint' => array('length'=>5),
			'mediumint' => array('length'=>8),
			'bigint' => array('length'=>20),
		);
		
		$typeValidators = array(
			'int' => 'int', 'tinyint' => 'int', 'char' => 'string',
			'varchar' => 'string', 'text' => 'string', 'mediumtext' => 'string',
			'enum' => 'enum', 'decimal' => 'int', 'date' => 'date',
			'datetime' => 'datetime', 'float' => 'int', 'double' => 'int'
		);
		
		foreach (self::$schema[self::$registering]["columns"] as $column => $col) {
			// Gather up SQL specific params to pass to validators
			$params = array_merge($col, array(
				'unsigned' => coal($col['unsigned'], null),
				'options' => coal($col['options'], null)
			), coal($typeParams[$col['type']], array()));
			
			self::validation($column, $typeValidators[$col['type']], $params);
			self::validation($column, 'length', $params);
		}
	}
	
	public static function blank($table) {
		return self::fetch($table);
	}
	
	public static function blankFromSql($sql, $merge=null, $mergeGrp="default") {
		$meta = new SqlMeta($sql);
		$obj = new StdClass;
		foreach ($meta->selects() as $select) {
			$table = $meta->realTable($select['table']);
			if (isset(self::$schema[$table]["columns"][$select['source']])) {
				$obj->$select['alias'] = self::$schema[$table]["columns"][$select['source']]['default'];
			}
		}
		WoolTable::setQueryMeta($obj, $meta);
		return WoolTable::fromArray($obj, $merge, $mergeGrp);
	}
	
	// Fetch is a simple way to get a single row from a single table.
	public static function fetch($table, $id=null, $merge=null, $mergeGrp="default") {
		$id = !is_numeric($id) ? id_param($id) : $id;
		
		if ($id) {
			$obj = new StdClass;
			$where = self::primaryWhereClause($table, $id);
			$obj = WoolDb::fetchRow("select * from {$table} where {$where}");
		} else {
			$obj = new StdClass;
			foreach (self::$schema[$table]["columns"] as $colName=>$col) {
				$obj->$colName = $col['default'];
			}
			WoolTable::setQueryMeta($obj, new SqlMeta($table, true));
		}
		return WoolTable::fromArray($obj, $merge, $mergeGrp);
	}
	
	// Shortcut to get a row set of a single table.
	public static function fetchAll($table, $where='', $params=null) {
		$where = guard($where, "where " . $where);
		return new RowSet("select * from {$table} {$where}", $params);
	}
	
	public static function fromArray($obj, $merge=null, $mergeGrp="default") {
		if (!$merge) {
			return $obj;
		}
		
		if (is_a($merge, "StdClass")) {
			$merge = get_object_vars($merge);
		}
		
		$merge = is_string($merge) ? param($merge) : $merge;
		$id = spl_object_hash($obj);
		$meta = self::$queryMeta[$id];
		
		foreach ($obj as $field => $value) {
			$table = $meta->columnTable($field);
			$source = $meta->columnSource($field);
			
			// Be careful to only copy safe columns across. If no safe columns are
			// defined they are all assumed to be safe, for ease of use. Otherwise
			// only copy the safe columns.
			if (isset(self::$mergeGroups[$table][$mergeGrp])
				&& !isset(self::$mergeGroups[$table][$mergeGrp][$source]))
			{
				continue;
			}
			
			if (isset($merge[$field])) {
				$obj->$field = $merge[$field];
			}
		}
		return $obj;
	}
	
	// Save and optionally validate one or more rows. Objs may be a single row or
	// many rows. Each row will be validated and if every field of every row is
	// valid a save will be attempted. Saves will write back to all source tables
	// of the row.
	public static function save($objs, $validate=true, $transaction=true, $onlyValidate=false) {
		$allValid = true;
		$stores = array();
		
		if (!is_array_like($objs)) {
			$objs = array($objs);
		}
		
		foreach ($objs as $obj) {
			$id = spl_object_hash($obj);
			$meta = self::$queryMeta[$id];
			
			$store = array();
			$srcTables = array();
			
			foreach ($meta->sourceTables() as $table=>$srcTable) {
				$srcTables[$srcTable] = $table;
				$store[$table] = array();
				$store[$table]['obj'] = $obj;
				$store[$table]['insert'] = false;
				$store[$table]['reqPrimaries'] = 0;
				$store[$table]['primaries'] = array();
				$store[$table]['values'] = array();
				$store[$table]['increment'] = null;
				
				if ($validate) {
					self::triggerEvent("preValidate", $obj, $table);
				}
				
				foreach (self::$schema[$srcTable]["columns"] as $colName=>$col) {
					if (isset($col['additional'])) {
						continue;
					}
					
					$alias = $meta->columnAlias($table, $colName);
					
					// Auto increment field is used to determine insert/update. A missing
					// field skips save for this table. Null causes an insert. All other
					// values cause an update.
					if ($col['increment']) {
						$store[$table]['increment'] = $alias;
						
						if (!property_exists($obj, $alias)) {
							unset($store[$table]);
							break;
						}
						if ($alias && !$obj->$alias) {
							$store[$table]['insert'] = true;
						}
					}
					
					if (!$alias) {
						continue;
					}
					
					// Store the primary keys separately to use for updates.
					if ($col['primary']) {
						if (!$col['increment']) {
							$store[$table]['reqPrimaries']++;
						}
						
						if (isset($obj->$alias)) {
							$store[$table]['primaries']["{$colName} = ?"] = $obj->$alias;
						}
					}
					
					if (isset($obj->$alias)) {
						$store[$table]['values'][$colName] = &$obj->$alias;
					}
				}
				
				// If an entire source table is completely empty, it was
				// probably a left join, so skip validation and don't attempt
				// to save.
				if (!isset($store[$table]) || (!$store[$table]['values'] && count($meta->sourceTables()) > 1)) {
					unset($store[$table]);
					continue;
				}
				
				// Validate
				if ($validate && !self::validateTable($table, $obj, $store[$table]['insert'])) {
					$allValid = false;
				}
			}
			
			$stores[] = array('store'=>$store, 'srcTables'=>$srcTables);
		}
		
		if (!$allValid || $onlyValidate) {
			return $allValid;
		}
		
		if ($transaction) {
			$trans = new TransactionRaii;
		}
		
		// Finally, take all the batched store requests and insert/update the
		// database.
		foreach ($stores as $store) {
			foreach ($store['store'] as $table=>$s) {
				$srcTable = $meta->realTable($table);
				
				self::triggerEvent("preSave", $s['obj'], $table);
				
				if (count($s['primaries']) < $s['reqPrimaries']) {
					trigger_error("Missing primary keys trying to save '{$srcTable}'.", E_USER_ERROR);
					continue;
				}
				
				// Copy across any referential keys from saved objects, where possible.
				if (isset(self::$schema[$srcTable]["keys"])) {
					foreach (self::$schema[$srcTable]["keys"] as $frnTbl=>$def) {
						foreach ($def["columns"] as $localCol=>$frnCol) {
							$localAlias = $meta->columnAlias($table, $localCol);
							if (!property_exists($s['obj'], $localAlias)) {
								continue;
							}
							
							if (!isset($store['srcTables'][$frnTbl])) {
								continue;
							}
							
							$frnObj = $store['store'][$store['srcTables'][$srcTbl]]['obj'];
							$frnAlias = $meta->columnAlias($store['srcTables'][$srcTbl], $colSrc);
							
							if (!property_exists($frnObj, $frnAlias)) {
								continue;
							}
							
							$s['obj']->$relAlias = $frnObj->$frnAlias;
							$s['values'][$relSrc] = $frnObj->$frnAlias;
						}
					}
				}
				
				// Send off to Zend_Db to do the save.
				if ($s['insert']) {
					self::triggerEvent("preInsert", $s['obj'], $table);
					WoolDb::insert($srcTable, $s['values']);
					$s['obj']->$s['increment'] = WoolDb::lastInsertId();
				}
				else {
					self::triggerEvent("preUpdate", $s['obj'], $table);
					WoolDb::update($srcTable, $s['values'], $s['primaries']);
				}
				
				self::triggerEvent("postSave", $s['obj'], $table);
			}
		}
		
		if ($transaction) {
			$trans->success();
		}
		return true;
	}
	
	private static function triggerEvent($name, $obj, $table) {
		$id = spl_object_hash($obj);
		$meta = self::$queryMeta[$id];
		$srcTable = $meta->realTable($table);
		$cls = coal(self::$registered[$srcTable], null);
		if (!method_exists($cls, $name)) {
			return;
		}
		
		$peal = self::peal($obj, $table);
		call_user_func(array($cls, $name), $peal);
	}
	
	// Make a copy of a row entirely on the database, incorporating any required
	// changes at the same time. Validation is skipped so changes requiring
	// validation should use the more standard fetch and save.
	public static function remoteCopy($table, $id, $changes=array(), $where=null) {
		$where = $where ? $where : self::primaryWhereClause($table, $id);
		$inserts = array();
		$selects = array();
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if ($col['additional'] || $col['increment']) {
				continue;
			}
			
			$inserts[] = $colName;
			
			if (isset($changes[$colName])) {
				$selects[] = WoolDb::quote($changes[$colName]);
			} else {
				$selects[] = $colName;
			}
		}
		
		$inserts = join(", ", $inserts);
		$selects = join(", ", $selects);
		
		WoolDb::exec("insert into {$table} ({$inserts}) select {$selects} from {$table} where {$where}");
		return WoolDb::lastInsertId();
	}
	
	// Build a where cause uniquely identifying a row by all its primary keys.
	private static function primaryWhereClause($table, $id) {
		$primaries = self::tablePrimaries($table);
		$where = array();
		
		if (count($primaries) == 1 && !is_array($id)) {
			return key($primaries) . "=" . $id;
		}
		
		foreach ($primaries as $colName=>$col) {
			if (!isset($id[$colName])) {
				trigger_error("Missing primary key", E_USER_ERROR);
			}
			$where[] = $colName . "=" . $id[$colName];
		}
		
		return join(" and ", $where);
	}
	
	private static function tablePrimaries($table) {
		$primaries = array();
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if ($col['primary']) {
				$primaries[$colName] = $col;
			}
		}
		return $primaries;
	}
	
	private static function tableAutoIncrement($table) {
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if ($col['increment']) {
				return $colName;
			}
		}
		return null;
	}
}

WoolTable::init();
