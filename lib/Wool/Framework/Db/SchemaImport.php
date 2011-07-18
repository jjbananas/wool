<?php

require_once('spyc/spyc.php');
require_once('Wool/Framework/Db/SqlTypes.php');
require_once('Wool/Framework/Db/Schema.php');
require_once('Wool/Framework/Db/ImportSql.php');

//WoolTable::exportYaml($GLOBALS['BASE_PATH'] . '/db/export/');


class SchemaImport {
	private static $import = array();
	
	// Current load state for each table during conversion from YAML. Important
	// for recursive/dependent loads.
	const LS_LOADING = 1;
	const LS_DONE = 2;
	static $loadState = array();
	
	private static $columnTypes = array(
		"default" => array(
			"name" => "",
			"type" => "int",
			"length" => null,
			"scale" => null,
			"default" => null,
			"nullable" => false,
			"primary" => false,
			"increment" => false,
			"unsigned" => false,
			"derived" => false,
			"validators" => array()
		)
	);

	
	public static function load($name) {
		Schema::clear();
		
		if (is_dir($name)) {
			$names = glob($name . "/*.yml");
		} else {
			$names = array($name);
		}
		
		self::$import = array();
		foreach ($names as $name) {
			self::$import = array_merge(self::$import, Spyc::YAMLLoad($name));
		}
		
		foreach (self::$import as $name=>$entry) {
			if (preg_match("/^column /", $name)) {
				$column = substr($name, 7); 
				self::$columnTypes[$column] = self::getColumnDef($column, $entry);
			} else if (preg_match("/^mixin /", $name)) {
				self::getTableDef(substr($name, 6), false, true);
			} else {
				self::getTableDef($name);
			}
		}
	}
	
	// Split SQL types. These can be in three normal forms: text, int(10), decimal(10,2)
	// and also a dot separated reference to an existing column.
	private static function splitTypeDef(&$result, $type) {
		// First check reference to another table.
		$regex = "/^(\w+)\.(\w+)$/";
		$matches = array();

		if (preg_match($regex, $type, $matches)) {
			self::getTableDef($matches[1], true);
			$column = Schema::column($matches[1], $matches[2]);
			$result["type"] = $column["type"];
			$result["length"] = $column["length"];
			$result["scale"] = $column["scale"];
			return;
		}
		
		// Now check normal SQL types.
		$regex = "/^(\w+)(\((\d+)(,(\d+))?\))?$/";
		$matches = array();
		
		if (!preg_match($regex, $type, $matches)) {
			trigger_error("{$type} is not recognised as an SQL type", E_USER_WARNING);
			return;
		}
		
		if (!SqlTypes::isValidDataType($matches[1])) {
			trigger_error("'{$matches[1]}' is not a valid datatype. Did you mean to create a custom column type?", E_USER_WARNING);
			return;
		}
		
		$result["type"] = $matches[1];
		if (isset($matches[3])) {
			$result["length"] = $matches[3];
		}
		if (isset($matches[5])) {
			$result["scale"] = $matches[5];
		}
	}

	private static function getColumnDef($colName, $col) {
		if (isset($col["type"]) && isset(self::$columnTypes[$col["type"]])) {
			$res = self::$columnTypes[$col["type"]];
			unset($col["type"]);
		} else {
			$res = self::$columnTypes["default"];
		}
		
		return self::mergeColumnDef($res, $colName, $col);
	}
	
	private static function getColumnValidatorDef(&$merge, $def) {
		foreach ($def as $name=>$params) {
			$merge["validators"][$name] = ($params ? $params : array());
		}
	}
	
	private static function mergeColumnDef($merge, $colName, $col) {
		$skip = array("history", "fetch", "sum");
		
		foreach ($col as $name=>$def) {
			if ($name == "type") {
				self::splitTypeDef($merge, $def);
			} else if ($name == "validation") {
				self::getColumnValidatorDef($merge, $def);
			} else if (in_array($name, $skip)) {
				continue;
			} else if (array_key_exists($name, $merge)) {
				$merge[$name] = $def;
			} else {
				trigger_error("{$name} is not a valid column definition", E_USER_WARNING);
			}
		}
		
		// Column names must always be given explicity. No merge.
		$merge["name"] = isset($col["name"]) ? $col["name"] : ucwords(join(' ', preg_split('/(?=[A-Z])/', $colName)));
		
		return $merge;
	}
	
	private static function baseHistoryTable($tblName) {
		$name = "history_{$tblName}";
		
		if (Schema::tableExists($name)) {
			return;
		}
		
		// Copy primary columns.
		$primaries = Schema::primaryColumns($tblName);
		
		foreach ($primaries as $primary) {
			$def = Schema::column($tblName, $primary);
			$def["increment"] = false;
			Schema::addColumn($name, $primary, $def);
		}
		
		// Set up standard history columns.
		Schema::addColumn($name, "changedOn", self::getColumnDef("changedOn", array(
			"type" => "datetime",
			"primary" => true
		)));
		
		Schema::addColumn($name, "cause", self::getColumnDef("cause", array(
			"type" => "enum",
			"length" => array("ins", "upd", "del")
		)));
		
		Schema::addInfo($name, "system", true);
	}
	
	private static function getHistoryColumnDef($tblName, $colName, $def) {
		$allowed = array("old", "new", "diff");
		
		// Force new values to always exist.
		$def .= "|new";
		
		$def = array_map("trim", explode("|", $def));
		$def = array_unique($def);
		
		self::baseHistoryTable($tblName);
		
		foreach ($def as $type) {
			if (!in_array($type, $allowed)) {
				trigger_error("Unrecognised history type", E_USER_WARNING);
				continue;
			}
			
			$name = "history_{$tblName}";
			$cname = "{$type}_{$colName}";
			$colDef = Schema::column($tblName, $colName);
			$colDef["nullable"] = true;
			$colDef["primary"] = false;
			$colDef["increment"] = false;
			Schema::addColumn($name, $cname, $colDef);
		}
		
		Schema::addInfo($tblName, "history", true);
		ImportMySql::$triggers[$tblName]["history"][$colName] = $def;
	}
	
	private static function getTableColumnDef($tblName, $colName, $col) {
		if ($col['type'] != "key") {
			$def = self::getColumnDef($colName, $col);
			Schema::addColumn($tblName, $colName, $def);
			
			if ($def['type'] == "varchar" && $def['length'] < 70) {
				Schema::addInfo($tblName, "titleColumn", $colName, false);
			}
			
			if (isset($col['history'])) {
				self::getHistoryColumnDef($tblName, $colName, $col['history']);
			}
			
			return;
		}
		
		// Copy columns from foreign primary key.
		self::getTableDef($colName, true);
		$primaries = Schema::primaryColumns($colName);
		
		if (!$primaries) {
			return;
		}
		
		$locals = array();
		$prefix = isset($col['prefix']) ? "{$col['prefix']}_" : "";
		foreach ($primaries as $primary) {
			$local = "{$prefix}{$primary}";
			$locals[$local] = $primary;
			$colDef = Schema::column($colName, $primary);
			$colDef["primary"] = isset($col["primary"]) ? $col["primary"] : false;
			$colDef["increment"] = false;
			Schema::addColumn($tblName, $local, $colDef);
		}
		
		// Set foreign key constraints.
		Schema::addKey($tblName, $colName, array(
			"name" => "FK__{$tblName}_{$colName}",
			"columns" => $locals,
			"references" => $colName,
			"update" => $col['update'],
			"delete" => $col['delete']
		));
		
		// Store inbound keys against the referenced table for faster lookup.
		Schema::addInbound($colName, $tblName);
		
		if (isset($col['history'])) {
			self::getHistoryColumnDef($tblName, $colName, $col['history']);
		}
	}
	
	private static function getIndexDef($tblName, $idxName, $index) {
		$unique = false;
		
		if (preg_match("/^unique /", $idxName)) {
			$idxName = substr($idxName, 7);
			$unique = true;
		}
		
		Schema::addIndex($tblName, $idxName, array(
			"unique" => $unique,
			"columns" => $index
		));
	}
	
	private static function getFetchTriggerDef($tblName, $colName, $col) {
		$expr = $col["fetch"];
		$matches = array();
		
		preg_match_all("/[\w\.]+/", $expr, $matches, PREG_OFFSET_CAPTURE);
		
		if (!isset($matches[0])) {
			return;
		}
		
		// Replace all dots in column selectors into underscores.
		$expr = preg_replace("/(\w)\.(\w)/", "$1_$2", $expr);
		
		$joins = array();
		$grow = 0;
		$srcTbl = null;
		$srcCol = null;
		
		// Each match is a table/column selector.
		foreach ($matches[0] as $match) {
			if (strpos($match[0], ".") === false) {
				// Local table selector, add "new."
				$expr = str_insert("new.", $expr, $match[1]+$grow);
				$grow += 4;
				
				if (!$srcTbl) {
					$srcTbl = $tblName;
					$srcCol = $match[0];
				}
			} else {
				// Foreign fetch. Build joins and store to temp vars.
				$expr = str_insert("var_", $expr, $match[1]+$grow);
				$grow += 4;
				
				$pieces = explode(".", $match[0]);
				$last = &$joins;
				while (count($pieces) > 2) {
					$piece = array_shift($pieces);
					$last[$piece] = array();
					$last = &$last[$piece];	
				}
				
				$last[$pieces[0]][$pieces[1]] = "var_" . str_replace(".", "_", $match[0]);
				
				if (!$srcTbl) {
					$srcTbl = $pieces[0];
					$srcCol = $pieces[1];
				}
			}
		}
		
		// Create target column
		self::getTableDef($srcTbl, true);
		Schema::addColumn($tblName, $colName, self::mergeColumnDef(
			Schema::column($srcTbl, $srcCol),
			$colName,
			$col
		));
		
		$expr = "set new.{$colName} = " . $expr . ";";
		
		ImportMySql::$triggers[$tblName]["fetch"] = array_merge_recursive(
			isset(ImportMySql::$triggers[$tblName]["fetch"]) ? ImportMySql::$triggers[$tblName]["fetch"] : array(),
			$joins
		);
		ImportMySql::$triggers[$tblName]["set"][] = $expr;
	}
	
	private static function getSumTriggerDef($tblName, $colName, $expr, $type) {
		$pieces = explode(".", $expr[$type]);
		if (count($pieces) != 2) {
			return;
		}
		
		// Create target column.
		self::getTableDef($pieces[0], true);
		
		$colDef = self::mergeColumnDef(
			Schema::column($pieces[0], $pieces[1]),
			$colName,
			$expr
		);
		$colDef["default"] = "0.00";
		Schema::addColumn($tblName, $colName, $colDef);
		
		// Register trigger data.
		ImportMySql::$triggers[$pieces[0]]["aggregate"][$tblName][$type][$pieces[1]] = $colName;
	}
	
	private static function getDerivedColumnDef($tblName, $colName, $col) {
		$allowed = array("fetch", "sum", "count");
		
		foreach ($col as $type=>$sql) {
			if (!in_array($type, $allowed)) {
				return;
			}
			
			if ($type == "fetch") {
				self::getFetchTriggerDef($tblName, $colName, $col);
			} else {
				self::getSumTriggerDef($tblName, $colName, $col, $type);
			}
			
			// Derived columns must be nullable so triggers have a chance to affect them. (MySQL Bug).
			Schema::setColumnValue($tblName, $colName, "nullable", true);
			Schema::setColumnValue($tblName, $colName, "derived", true);
		}
	}
	
	private static function tableToCamelCase($tblName) {
		return camelCase(explode('_', $tblName));
	}
	
	private static function checkTableRequirements($tblName) {
		// Give the table a good default name if not provided.
		if (Schema::displayName($tblName) == $tblName) {
			Schema::addInfo($tblName, "name", ucwords(join(' ', explode('_', $tblName))));
		}
		
		if (!Schema::uniqueColumn($tblName)) {
			$colName = self::tableToCamelCase($tblName) . "Id";
			
			Schema::addColumn($tblName, $colName, self::mergeColumnDef(
				self::$columnTypes["default"],
				$colName,
				array(
					"increment" => true
				)
			));
			
			Schema::addIndex($tblName, "unique_{$colName}", array(
				"unique" => true,
				"columns" => array($colName)
			));
		}
	}

	private static function getTableDef($tblName, $dependent=false, $mixin=false) {
		$importTbl = ($mixin ? "mixin {$tblName}" : $tblName);
		
		if (isset(self::$loadState[$tblName])) {
			return;
		}
		
		self::$loadState[$tblName] = self::LS_LOADING;
		
		$allowed = array("columns", "derived", "index");
		
		if (!isset(self::$import[$importTbl])) {
			trigger_error("'{$tblName}' not found in database dictionary", E_USER_WARNING);
		}
		
		if ($mixin) {
			Schema::flagMixin($tblName);
		}
		
		foreach (self::$import[$importTbl] as $section=>$def) {
			if ($section == "columns") {
				foreach ($def as $name=>$col) {
					self::getTableColumnDef($tblName, $name, $col);
				}
			}
			else if ($section == "index") {
				foreach ($def as $name=>$index) {
					self::getIndexDef($tblName, $name, $index);
				}
			}
			else if ($section == "derived") {
				foreach ($def as $name=>$col) {
					self::getDerivedColumnDef($tblName, $name, $col);
				}
			}
			else if ($section == "info") {
				foreach ($def as $name=>$col) {
					Schema::addInfo($tblName, $name, $col);
				}
			}
			else if ($section == "mixin") {
				foreach ($def as $name=>$mixTbl) {
					Schema::applyMixin($tblName, $name);
				}
			}
			else {
				trigger_error("{$section} is not a valid table section", E_USER_WARNING);
			}
		}
		
		if (!$mixin) {
			self::checkTableRequirements($tblName);
		}
		
		self::$loadState[$tblName] = self::LS_DONE;
	}
}
