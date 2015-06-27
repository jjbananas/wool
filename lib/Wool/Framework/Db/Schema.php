<?php

require_once('Wool/Framework/Db/SqlTypes.php');

class Schema {
	// The global database schema, created from the database.
	private static $schema = array();
	private static $mixins = array();
	
	// Start up from a pre-existing cache.
	public function loadFromCache() {
		if (DEVELOPER && !file_exists($GLOBALS['BASE_PATH'] . '/var/database/schema.php')) {
			require_once('Wool/Framework/Db/SchemaExport.php');
			self::$schema = exportSchema($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
		} else {
			self::$schema = require($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
		}
	}
	
	public function saveToCache() {
		return file_put_contents_mkdir($GLOBALS['BASE_PATH'] . '/var/database/schema.php', "<?php\nreturn " . var_export(self::$schema, true) . ";\n");
	}
	
	public function clear() {
		self::$schema = array();
	}
	
	public function debug() {
		debug(self::$schema);
	}
	
	public static function fullSchema() {
		return self::$schema;
	}
	
	public static function cachedSchema() {
		return require($GLOBALS['BASE_PATH'] . '/var/database/schema.php');
	}
	
	// Export the current database schema to YAML. One file per table.
	public static function exportAsYaml($dir) {
		foreach (self::$schema as $name=>$table) {
			file_put_contents_mkdir($dir . $name . '.yaml', Spyc::YAMLDump($table));
		}
	}
	
	public static function flagMixin($table) {
		self::$mixins[$table] = array();
	}
	
	public static function applyMixin($table, $mixin) {
		self::$schema[$table] = array_merge_recursive_keys(self::$schema[$table], self::$mixins[$mixin]);
	}
	
	public function addColumn($table, $name, $def) {
		if (isset(self::$mixins[$table])) {
			self::$mixins[$table]["columns"][$name] = $def;
		} else {
			self::$schema[$table]["columns"][$name] = $def;
		}
	}
	
	public function setColumnValue($table, $column, $field, $value) {
		if (isset(self::$mixins[$table])) {
			self::$mixins[$table]["columns"][$column][$field] = $value;
		} else {
			self::$schema[$table]["columns"][$column][$field] = $value;
		}
	}
	
	public function addInfo($table, $name, $value, $overwrite=true) {
		if (!$overwrite && isset(self::$schema[$table]["info"][$name])) {
			return;
		}
		self::$schema[$table]["info"][$name] = $value;
	}
	
	public function addIndex($table, $name, $def) {
		if (isset(self::$mixins[$table])) {
			self::$mixins[$table]["index"][$name] = $def;
		} else {
			self::$schema[$table]["index"][$name] = $def;
		}
	}
	
	public function addKey($table, $name, $def) {
		if (isset(self::$mixins[$table])) {
			self::$mixins[$table]["keys"][$name] = $def;
		} else {
			self::$schema[$table]["keys"][$name] = $def;
		}
	}
	
	public function addInbound($table, $def) {
		if (isset(self::$mixins[$table])) {
			self::$mixins[$table]["inbound"][] = $def;
		} else {
			self::$schema[$table]["inbound"][] = $def;
		}
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

	public static function tableSequenced($table) {
		return isset(self::$schema[$table]["info"]["sequenced"]);
	}
	
	public static function isSystemTable($table) {
		return isset(self::$schema[$table]["info"]["system"]);
	}
	
	public static function isStdTable($table) {
		return !self::isSystemTable($table);
	}
	
	public static function isJoinTable($table) {
		if (!isset(self::$schema[$table])) {
			return false;
		}
		
		// Two primary keys plus the auto-increment column.
		if (count(self::$schema[$table]["columns"]) != 3) {
			return false;
		}
		
		// Two primary keys expected.
		if (count(self::$schema[$table]["keys"]) != 2) {
			return false;
		}
		
		return true;
	}
	
	public static function referencedTables($table, $ignore=null) {
		$tables = array();
		
		foreach (self::$schema[$table]["keys"] as $key) {
			if ($ignore && $key["references"] != $ignore) {
				$tables[] = $key["references"];
			}
		}
		
		return $tables;
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
	
	public static function titleColumn($table) {
		return coal(self::$schema[$table]["info"]["titleColumn"], null);
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

		if ($col['system']) {
			return false;
		}
		
		return true;
	}
	
	
	public static function filterableColumns($table) {
		$cols = array();
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if (self::columnFilterable($table, $colName)) {
				$cols[$colName] = $col;
			}
		}
		
		return $cols;
	}
	
	public static function columnFilterable($table, $column) {
		$col = self::$schema[$table]["columns"][$column];
		
		if (SqlTypes::isDate($col['type'])) {
			return true;
		}
		
		if ($col['type'] == "enum") {
			return true;
		}
		
		return false;
	}
	
	
	public static function getColumnType($table, $column) {
		return self::$schema[$table]["columns"][$column]["type"];
	}

	public static function getColumnAttr($table, $column, $attr) {
		return isset(self::$schema[$table]["columns"][$column]["attrs"][$attr]) ? self::$schema[$table]["columns"][$column]["attrs"][$attr] : null;
	}
	
	public static function columnName($table, $column) {
		return
			isset(self::$schema[$table]["columns"][$column]["name"])
			? self::$schema[$table]["columns"][$column]["name"]
			: $column;
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
	
	// Columns small enough to be used in summary situations, ie data grids.
	public static function summaryColumns($table) {
		$cols = array();
		
		foreach (self::$schema[$table]["columns"] as $colName=>$col) {
			if ($col["length"] > 256) {
				continue;
			}
			
			if ($col["type"] == "binary") {
				continue;
			}
			
			$cols[] = $colName;
		}
		
		return $cols;
	}

	// Summary columns excluding old_ or diff_ columns for history tables.
	public static function historySummaryColumns($table) {
		$summaryColumns = self::summaryColumns($table);
		$historyColumns = array();

		foreach ($summaryColumns as $col) {
			if (strpos($col, "diff_") === 0 || strpos($col, "old_") === 0) {
				continue;
			}

			$historyColumns[] = $col;
		}

		return $historyColumns;
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
		if (!isset(self::$schema[$table])) {
			return array();
		}
		
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
	
	public static function joinCondition($src, $dest, $srcAlias="js", $destAlias="jd") {
		$key = self::$schema[$src]["keys"][$dest];
		$join = array();
		
		foreach ($key["columns"] as $local=>$foreign) {
			$join[] = "{$srcAlias}.{$local} = {$destAlias}.{$foreign}";
		}
		
		return join(" and ", $join);
	}
	
	public static function keyJoins($table) {
		if (!isset(self::$schema[$table]["keys"])) {
			return array();
		}
		
		$joins = array();
		$j = 1;
		
		foreach (self::$schema[$table]["keys"] as $name=>$key) {
			$titleCol = self::titleColumn($key["references"]);
			if (!$titleCol) {
				continue;
			}
			
			$cond = array();
			foreach ($key["columns"] as $local=>$foreign) {
				$cond[] = "j{$j}.{$local} = t.{$foreign}";
			}
			$cond = join(" and ", $cond);
			$joins["j{$j}.{$titleCol} as {$name}_title"] = "join {$key["references"]} j{$j} on {$cond}";
			$j++;
		}
		
		return $joins;
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
	
	public static function enumOptions($table, $column) {
		return coal(self::$schema[$table]["columns"][$column]["length"], array());
	}
	
	public static function length($table, $column) {
		return coal(self::$schema[$table]["columns"][$column]["length"], null);
	}
}
