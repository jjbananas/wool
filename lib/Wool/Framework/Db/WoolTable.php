<?php

require_once('Wool/Framework/Db/Schema.php');

class WoolTable {
	private static $dbFiles = array();
	
	private static $registered = array();
	private static $mergeGroups = array();
	
	private static $queryMeta = array();
	private static $pealCache = array();
	
	public static function addTableDefinitionFiles($dir) {
		$di = new RecursiveDirectoryIterator($dir);
		foreach (new RecursiveIteratorIterator($di) as $file) {
			self::$dbFiles[$file->getBasename(".php")] = $file->getPathname();
		}
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
		
		self::registerTable($table);
		return WoolValidation::getFor($table, $source);
	}
	
	public static function srcTable($obj, $col) {
		$id = spl_object_hash($obj);
		$meta = self::$queryMeta[$id];
		return $meta->columnTable($col);
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
		
		foreach (Schema::allColumns($table) as $colName=>$col) {
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
			if (!self::validateColumn($table, $column, $colVal, $obj)) {
				$valid = false;
			}
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
		self::registerTable($table);
		return WoolValidation::validate($table, $column, $obj, $value, Schema::columnName($table, $column));
	}
	
	private static function registerTable($name) {
		if (isset(self::$registered[$name])) {
			return;
		}
		
		$cls = self::tableToCamelCase($name);
		self::$registered[$name] = $cls;
		
		if (!Schema::tableExists($name)) {
			trigger_error("Table '{$name}' not found in schema cache", E_USER_NOTICE);
		}
		
		if (isset(self::$dbFiles[$cls])) {
			require_once(self::$dbFiles[$cls]);
		}
		
		self::registerTableValidators($name);
	}
	
	private static function tableToCamelCase($tblName) {
		return camelCase(explode('_', $tblName), true);
	}
	
	public static function keySearch($table, $search) {
		$u = Schema::uniqueColumn($table);
		
		return new RowSet(<<<SQL
select {$u} id
from {$table}
where {$u} = ?
SQL
		, $search);
	}
	
	private static function registerTableValidators($table) {
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
		
		foreach (Schema::allColumns($table) as $column => $col) {
			// Gather up SQL specific params to pass to validators
			$params = array_merge($col, array(
				'unsigned' => coal($col['unsigned'], null),
				'options' => coal($col['options'], null)
			), coal($typeParams[$col['type']], array()));
			
			WoolValidation::add($table, $column, $typeValidators[$col['type']], $params);
			WoolValidation::add($table, $column, 'length', $params);
			
			if (!$col['nullable']) {
				WoolValidation::add($table, $column, 'required', $params);
			}
			
			// Now add custom defined validators
			foreach ($col['validators'] as $validator=>$params) {
				WoolValidation::add($table, $column, $validator, $params);
			}
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
			$col = Schema::column($table, $select['source']);
			if ($col) {
				$obj->$select['alias'] = $col['default'];
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
			foreach (Schema::allColumns($table) as $colName=>$col) {
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
				self::registerTable($srcTable);
				
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
				
				foreach (Schema::allColumns($srcTable) as $colName=>$col) {
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
			$trans = new Transaction;
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
				foreach (Schema::allKeys($srcTable) as $frnTbl=>$def) {
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
		
		foreach (Schema::allColumns($table) as $colName=>$col) {
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
	
	// Delete a row from a table using the unique ID.
	public static function delete($table, $id) {
		$id = !is_numeric($id) ? id_param($id) : $id;
		$unique = Schema::uniqueColumn($table);
		
		if (!$unique) {
			return false;
		}
		
		return WoolDb::query("delete from {$table} where {$unique} = ?", $id);
	}
	
	// Build a where cause uniquely identifying a row by all its primary keys.
	private static function primaryWhereClause($table, $id) {
		$primaries = Schema::primaryColumns($table);
		$where = array();
		
		if (count($primaries) == 1 && !is_array($id)) {
			return $primaries[0] . "=" . $id;
		}
		
		foreach ($primaries as $colName) {
			if (!isset($id[$colName])) {
				trigger_error("Missing primary key", E_USER_ERROR);
			}
			$where[] = $colName . "=" . $id[$colName];
		}
		
		return join(" and ", $where);
	}
}

Schema::loadFromCache();
WoolTable::addTableDefinitionFiles($GLOBALS['BASE_PATH'] . '/lib/Wool/App/');
