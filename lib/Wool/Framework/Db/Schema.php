<?php

function array_diff_recursive($aArray1, $aArray2) {
  $aReturn = array();

	foreach ($aArray1 as $mKey => $mValue) {
		if (array_key_exists($mKey, $aArray2)) {
			if (is_array($mValue)) {
				$aRecursiveDiff = array_diff_recursive($mValue, $aArray2[$mKey]);
				if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
			} else {
				if ($mValue != $aArray2[$mKey]) {
					$aReturn[$mKey] = $mValue;
				}
			}
		} else {
			$aReturn[$mKey] = $mValue;
		}
	}
	return $aReturn;
}

class Schema {
	// The global database schema, created from the database.
	private static $schema = array();
	
	// Start up from a pre-existing cache.
	public function loadFromCache() {
		if (DEVELOPER && !file_exists($GLOBALS['BASE_PATH'] . '/var/database/schema.php')) {
			require_once('Wool/Db/SchemaExport.php');
			exportSchema($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
		}

		self::$schema = require($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
	}
	
	public function saveToCache() {
		return file_put_contents_mkdir($GLOBALS['BASE_PATH'] . '/var/database/schema.php', "<?php\nreturn " . var_export(self::$schema, true) . ";\n");
	}
	
	public function diffFromCache() {
		$old = require($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
		
		debug(array_diff_recursive($old, self::$schema));
		debug(array_diff_recursive(self::$schema, $old));
	}
	
	// Export the current database schema to YAML. One file per table.
	public static function exportAsYaml($dir) {
		foreach (self::$schema as $name=>$table) {
			file_put_contents_mkdir($dir . $name . '.yaml', Spyc::YAMLDump($table));
		}
	}
	
	
	public function addColumn($table, $name, $def) {
		self::$schema[$table]["columns"][$name] = $def;
	}
	
	public function setColumnValue($table, $column, $field, $value) {
		self::$schema[$table]["columns"][$column][$field] = $value;
	}
	
	public function addInfo($table, $name, $value) {
		self::$schema[$table]["info"][$name] = $value;
	}
	
	public function addIndex($table, $name, $def) {
		self::$schema[$table]["index"][$name] = $def;
	}
	
	public function addKey($table, $name, $def) {
		self::$schema[$table]["keys"][$name] = $def;
	}
	
	public function addInbound($table, $def) {
		self::$schema[$table]["inbound"][] = $def;
	}
	
	
	
	public function tableExists($table) {
		return isset(self::$schema[$table]);
	}
	
	public function tables($all=false) {
		if ($all) {
			return array_keys(self::$schema);
		}
		return array_filter(array_keys(self::$schema), array("self", "isStdTable"));
	}
	
	public static function tableClass($table) {
		return ucwords($table);
	}
	
	public static function tableHasHistory($table) {
		return isset(self::$schema[$table]["info"]["history"]);
	}
	
	public static function isSystemTable($table) {
		return isset(self::$schema[$table]["info"]["system"]);
	}
	
	public static function isStdTable($table) {
		return !self::isSystemTable($table);
	}
	
	public static function displayName($table) {
		return coal(self::$schema[$table]["info"]["name"], $table);
	}
	
	public static function shortName($table) {
		return coal(self::$schema[$table]["info"]["shortName"], self::displayName($table));
	}
	
	public static function uniqueColumn($table) {
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if ($col['increment']) {
				return $colName;
			}
		}
		return null;
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
	
	
	public static function keyColumns($table) {
		$cols = array();
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if (self::columnIsKey($table, $colName)) {
				$cols[$colName] = $col;
			}
		}
		
		return $cols;
	}
	
	// Returns the referenced table if the column is a foreign key.
	public static function columnIsKey($table, $column) {
		if (!isset(self::$schema[$table]["keys"])) {
			return false;
		}
		
		foreach (self::$schema[$table]["keys"] as $key) {
			if (isset($key["columns"][$column])) {
				return $key["references"];
			}
		}
		
		return false;
	}
	
	
	
	public static function indexedColumns($table) {
		$cols = array();
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if (self::columnIsIndexed($table, $colName)) {
				$cols[$colName] = $col;
			}
		}
		
		return $cols;
	}
	
	public static function columnIsIndexed($table, $column) {
		if (!isset(self::$schema[$table]["index"])) {
			return false;
		}
		
		foreach (self::$schema[$table]["index"] as $index) {
			if (in_array($column, $index["columns"])) {
				return true;
			}
		}
		
		return false;
	}
	
	
	public static function searchColumns($table) {
		return array_keys(self::indexedColumns($table))
			+ array_keys(self::keyColumns($table))
			+ self::primaryColumns($table);
	}
	
	
	public static function keyCondition($table, $key, $localNamespace=null, $foreignNamespace=null) {
		if (!isset(self::$schema[$table]["keys"][$key])) {
			return "";
		}
		
		$ln = $localNamespace ? "{$localNamespace}." : "";
		$fn = $foreignNamespace ? "{$foreignNamespace}." : "";
		
		$key = self::$schema[$table]["keys"][$key];
		$cond = array();
		foreach ($key["columns"] as $local=>$foreign) {
			$cond[] = "{$ln}{$local} = {$fn}{$foreign}";
		}
		return join(" and ", $cond);
	}
	
	public static function primaryColumns($table) {
		$primaries = array();
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if ($col['primary']) {
				$primaries[] = $colName;
			}
		}
		return $primaries;
	}
	
	public static function primaryCondition($table, $key, $item, $namespace=null) {
		if (!isset(self::$schema[$table]["keys"][$key])) {
			return "";
		}
		
		$n = $namespace ? "{$namespace}." : "";
		
		$key = self::$schema[$table]["keys"][$key];
		$cond = array();
		foreach ($key["columns"] as $local=>$foreign) {
			$cond[] = "{$n}{$local} = {$item->$foreign}";
		}
		return join(" and ", $cond);
	}
	
	public static function inboundKeys($table) {
		return coal(self::$schema[$table]["inbound"], array());
	}
	
	public static function allColumns($table) {
		return self::$schema[$table]["columns"];
	}
	
	public static function column($table, $column) {
		return coal(self::$schema[$table]["columns"][$column], null);
	}
	
	public static function allKeys($table) {
		return coal(self::$schema[$table]["keys"], array());
	}
}
