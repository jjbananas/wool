<?php

class EvanceTable {
	// The global database schema, created from the database.
	// ONLY PUBLIC so that schema.php can access it. DO NOT USE externally.
	public static $schema = array();
	
	private static $additionalColumns = array();
	private static $registering = null;
	private static $registered = array();
	private static $names = array();
	private static $mergeGroups = array();
	
	private static $queryMeta = array();
	private static $pealCache = array();
		
	public static function validation($column, $type, $params=array()) {
		EvanceValidation::add(self::$registering, $column, $type, $params);
	}
	
	public static function name($column, $pretty) {
		self::$names[self::$registering][$column] = $pretty;
	}
	
	public static function nameFor($table, $column) {
		return coal(self::$names[$table][$column], $column);
	}
	
	public static function nullable(/*...*/) {
		$cols = func_get_args();
		foreach ($cols as $column) {
			self::$schema[self::$registering][$column]['nullable'] = true;
		}
	}
	
	public static function defaultValue($column, $value) {
		self::$schema[self::$registering][$column]['default'] = $value;
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
		if (isset(self::$schema[self::$registering][$column])) {
			trigger_error("Attempting to override existing database column: '{$column}'", E_USER_ERROR);
		}
		
		self::$schema[self::$registering][$column] = array (
			'name' => $column,
			'default' => $default,
			'nullable' => $nullable,
			'type' => $type,
			'length' => $length,
			'scale' => null,
			'primary' => false,
			'auto_increment' => false,
			'additional' => true
		);
	}
	
	public static function setQueryMeta($obj, $meta) {
		$id = spl_object_hash($obj);
		self::$queryMeta[$id] = $meta;
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
		
		return EvanceValidation::getFor($table, $source);
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
		
		if (!isset(self::$registered[$table])) {
			trigger_error("Unregistered table '{$table}'", E_USER_ERROR);
		}
		
		foreach (self::$schema[$table] as $col) {
			$column = $meta->columnAlias($tableAlias, $col['name']);
			
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
			if (is_null($colVal) && ($col['nullable'] || ($col['primary'] && $col['auto_increment']))) {
				continue;
			}
			
			// Test all attached validators.
			$pretty = isset(self::$names[$table][$column]) ? self::$names[$table][$column] : $column;
			
			if (!EvanceValidation::validate($table, $column, $obj, $colVal, $pretty)) {
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
	
	public static function registerTable($cls, $name) {
		self::$registered[$name] = $cls;
		self::$registering = $name;
		if (!isset(self::$schema[$name])) {
			self::$schema[$name] = array();
		}
		call_user_func(array($cls, 'define'));
		self::registerTableTypeValidators();
		self::$registering = null;
	}
	
	public static function allColumns($table) {
		return self::$schema[$table];
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
		
		foreach (self::$schema[self::$registering] as $column => $col) {
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
			if (isset(self::$schema[$table][$select['source']])) {
				$obj->$select['alias'] = self::$schema[$table][$select['source']]['default'];
			}
		}
		EvanceTable::setQueryMeta($obj, $meta);
		return EvanceTable::fromArray($obj, $merge, $mergeGrp);
	}
	
	// Fetch is a simple way to get a single row from a single table.
	public static function fetch($table, $id=null, $merge=null, $mergeGrp="default") {
		$id = !is_numeric($id) ? id_param($id) : $id;
		
		if ($id) {
			$obj = new StdClass;
			$where = self::primaryWhereClause($table, $id);
			$obj = EvanceDb::fetchRow("select * from {$table} where {$where}");
		} else {
			$obj = new StdClass;
			foreach (self::$schema[$table] as $col) {
				$obj->$col['name'] = $col['default'];
			}
			EvanceTable::setQueryMeta($obj, new SqlMeta($table, true));
		}
		return EvanceTable::fromArray($obj, $merge, $mergeGrp);
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
			
			foreach ($meta->sourceTables() as $table=>$srcTable) {
				$store[$table] = array();
				$store[$table]['obj'] = $obj;
				$store[$table]['insert'] = false;
				$store[$table]['reqPrimaries'] = 0;
				$store[$table]['primaries'] = array();
				$store[$table]['values'] = array();
				$store[$table]['auto_increment'] = null;
				
				if ($validate) {
					self::triggerEvent("preValidate", $obj, $table);
				}
				
				foreach (self::$schema[$srcTable] as $col) {
					if ($col['additional']) {
						continue;
					}
					
					$alias = $meta->columnAlias($table, $col['name']);
					
					// Auto increment field is used to determine insert/update. A missing
					// field skips save for this table. Null causes an insert. All other
					// values cause an update.
					if ($col['auto_increment']) {
						$store[$table]['auto_increment'] = $alias;
						
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
						if (!$col['auto_increment']) {
							$store[$table]['reqPrimaries']++;
						}
						
						if (isset($obj->$alias)) {
							$store[$table]['primaries']["{$col['name']} = ?"] = $obj->$alias;
						}
					}
					
					if (isset($obj->$alias)) {
						$store[$table]['values'][$col['name']] = &$obj->$alias;
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
			
			$stores[] = $store;
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
			foreach ($store as $table=>$s) {
				$srcTable = $meta->realTable($table);
				
				self::triggerEvent("preSave", $s['obj'], $table);
				
				if (count($s['primaries']) < $s['reqPrimaries']) {
					trigger_error("Missing primary keys trying to save '{$srcTable}'.", E_USER_ERROR);
					continue;
				}
				
				// Send off to Zend_Db to do the save.
				if ($s['insert']) {
					self::triggerEvent("preInsert", $s['obj'], $table);
					EvanceDb::insert($srcTable, $s['values']);
					$s['obj']->$s['auto_increment'] = EvanceDb::lastInsertId();
				}
				else {
					self::triggerEvent("preUpdate", $s['obj'], $table);
					EvanceDb::update($srcTable, $s['values'], $s['primaries']);
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
		$cls = self::$registered[$srcTable];
		if (!method_exists($cls, $name)) {
			return;
		}
		
		$peal = self::peal($obj, $table);
		call_user_func(array($cls, $name), $peal);
	}
	
	// Make a copy of a row entirely on the database, incorporating any required
	// changes at the same time. Validation is skipped so changes requiring
	// validation should use the more standard fetch and save.
	public static function remoteCopy($table, $id, $changes=array()) {
		$where = self::primaryWhereClause($table, $id);
		$inserts = array();
		$selects = array();
		
		foreach (self::$schema[$table] as $col) {
			if ($col['additional'] || $col['auto_increment']) {
				continue;
			}
			
			$inserts[] = $col['name'];
			
			if (isset($changes[$col['name']])) {
				$selects[] = EvanceDb::quote($changes[$col['name']]);
			} else {
				$selects[] = $col['name'];
			}
		}
		
		$inserts = join(", ", $inserts);
		$selects = join(", ", $selects);
		
		EvanceDb::exec("insert into {$table} ({$inserts}) select {$selects} from {$table} where {$where}");
		return EvanceDb::lastInsertId();
	}
	
	// Build a where cause uniquely identifying a row by all its primary keys.
	private static function primaryWhereClause($table, $id) {
		$id = is_array($id) ? $id : array($id);
		$primaries = self::tablePrimaries($table);
		$where = array();
		
		if (count($primaries) == 1 && count($id) == 1) {
			return $primaries[0]['name'] . "=" . $id[0];
		}
		
		foreach ($primaries as $col) {
			if (!isset($id[$col['name']])) {
				trigger_error("Missing primary key", E_USER_ERROR);
			}
			$where[] = $col['name'] . "=" . $id[$col['name']];
		}
		
		return join(" and ", $where);
	}
	
	private static function tablePrimaries($table) {
		$primaries = array();
		foreach (self::$schema[$table] as $col) {
			if ($col['primary']) {
				$primaries[] = $col;
			}
		}
		return $primaries;
	}
	
	private static function tableAutoIncrement($table) {
		foreach (self::$schema[$table] as $col) {
			if ($col['auto_increment']) {
				return $col['name'];
			}
		}
		return null;
	}
}
