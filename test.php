<?php

require_once('./lib/Wool/Application.php');

//WoolTable::exportYaml($GLOBALS['BASE_PATH'] . '/db/export/');


class SchemaInfo {
	private static $import = array();
	private static $tables = array();
	private static $triggers = array();
	private static $errors = array();
	
	// Current load state for each table during conversion from YAML. Important
	// for recursive/dependent loads.
	const LS_LOADING = 1;
	const LS_DONE = 2;
	static $loadState = array();
	
	private static $columnTypes = array(
		"default" => array(
			"type" => "int",
			"length" => 10,
			"scale" => 0,
			"default" => null,
			"nullable" => false,
			"primary" => false,
			"increment" => false,
			"unsigned" => false
		)
	);

	
	public static function load($name) {
		self::$import = Spyc::YAMLLoad($name);

		foreach (self::$import as $name=>$entry) {
			if (preg_match("/^column /", $name)) {
				$column = substr($name, 7); 
				self::$columnTypes[$column] = self::getColumnDef($name, $entry);
			} else {
				self::getTableDef($name);
			}
		}
		
		//debug(self::$tables);
	}
	
	public static function generateSql() {
		$sql = array();
		
		foreach (self::$tables as $name=>$table) {
			$out = '';
			$lines = array();
			foreach ($table["columns"] as $colName=>$column) {
				$lines[] = self::columnSql($colName, $column);
			}
			
			$primary = self::primaryColumns($name);
			$primary = join(", ", $primary);
			if ($primary) {
				$lines[] = "primary key ({$primary})";
			}
			
			$indices = isset(self::$tables[$name]["index"]) ? self::$tables[$name]["index"] : array();
			foreach ($indices as $idxName=>$index) {
				$indexSql = array();
				if ($index["unique"]) {
					$indexSql[] = "unique";
				}
				$indexSql[] = "index";
				$indexSql[] = $idxName;
				$indexSql[] = "(" . join(", ", $index["columns"]) . ")";
				$lines[] = join(" ", $indexSql);
			}
			
			$keys = isset(self::$tables[$name]["keys"]) ? self::$tables[$name]["keys"] : array();
			foreach ($keys as $key) {
				$lines[] = sprintf(
					"constraint `%s` foreign key (%s) references `%s` (%s) on update %s on delete %s",
					$key['name'],
					join(", ", array_keys($key['columns'])),
					$key['references'],
					join(", ", $key['columns']),
					$key['update'],
					$key['delete']
				);
			}
			
			$out .= "create table `{$name}` (\n";
			$out .= join(",\n", $lines);
			$out .= "\n)\n";
			$out .= "collate='utf8_general_ci'\n";
			$out .= "engine=InnoDB;\n";
			$sql[] = $out;
		}
		
		return $sql;
	}
	
	private static function generateFetchSql(&$declares, &$selects, &$joins, $table, $refTbl, $fetch) {
		foreach ($fetch as $column=>$var) {
			if (is_string($var)) {
				// Add declare
				$varType = self::columnType(self::$tables[$refTbl]["columns"][$column]);
				$declares[] = "declare {$var} {$varType};";
				$selects["{$refTbl}.{$column}"] = $var;
				$joins[] = "join {$refTbl} on " . self::keyCondition($table, $refTbl, $table, $refTbl);
			} else {
				// Deeper join so recurse.
				self::generateFetchSql($declares, $selects, $joins, $refTbl, $column, $var);
			}
		}
	}
	
	public static function generateTriggerSql() {
		$triggers = array();
		
		debug(self::$triggers);
		
		foreach (self::$triggers as $tblName=>$table) {
			$declares = array();
			$sqls = array();
			
			// FETCH automations.
			if (isset($table["fetch"])) {
				foreach ($table["fetch"] as $refTbl=>$fetch) {
					$selects = array();
					$joins = array();
					
					self::generateFetchSql($declares, $selects, $joins, $tblName, $refTbl, $fetch);
					array_shift($joins);
					
					$condition = self::keyCondition($tblName, $refTbl, "new", $refTbl);
					
					$sqls[] = sprintf(<<<SQL
select %s into %s
from {$refTbl}
%s
where {$condition};
%s
SQL
					,
						join(", ", array_keys($selects)),
						join(", ", $selects),
						join("\n", $joins),
						join("\n", $table['set'])
					);
				}
			}
			
			// SUM automations
			if (isset($table["aggregate"])) {
				foreach ($table["aggregate"] as $refTbl=>$ag) {
					$sets = array();
					
					foreach ($ag as $type=>$group) {
						foreach ($group as $local=>$foreign) {
							if ($type == "sum") {
								$sets[] = "t.{$foreign} = t.{$foreign} + new.{$local}";
							} else if ($type == "count") {
								$sets[] = "t.{$foreign} = t.{$foreign} + 1";
							}
						}
					}
					
					$condition = self::keyCondition($tblName, $refTbl, "new", "t");
					$sets = join(",\n", $sets);
					
					$sqls[] = <<<SQL
update {$refTbl} t
set
{$sets}
where {$condition};
SQL;
				}
			}
			
			$declares = join("\n", $declares);
			$sqls = join("\n\n", $sqls);
			
			$sql = <<<SQL
CREATE TRIGGER `tbi_{$tblName}`
BEFORE INSERT ON `{$tblName}`
FOR EACH ROW BEGIN
{$declares}

{$sqls}
END;
SQL;
			
			$triggers[] = $sql;
		}
		
		return $triggers;
	}
	
	private static function primaryColumns($tableName) {
		$primaries = array();
		foreach (self::$tables[$tableName]["columns"] as $name=>$col) {
			if ($col['primary']) {
				$primaries[] = $name;
			}
		}
		return $primaries;
	}
	
	private static function keyCondition($table, $key, $localNamespace=null, $foreignNamespace=null) {
		if (!isset(self::$tables[$table]["keys"][$key])) {
			return "";
		}
		
		$ln = $localNamespace ? "{$localNamespace}." : "";
		$fn = $foreignNamespace ? "{$foreignNamespace}." : "";
		
		$key = self::$tables[$table]["keys"][$key];
		$cond = array();
		foreach ($key["columns"] as $local=>$foreign) {
			$cond[] = "{$ln}{$local} = {$fn}{$foreign}";
		}
		return join(" and ", $cond);
	}

	// Split SQL types. These can be in three forms: text, int(10), decimal(10,2)
	private static function splitTypeDef(&$result, $type) {
		$regex = "/^(\w+)(\((\d+)(,(\d+))?\))?$/";
		$matches = array();
		
		if (!preg_match($regex, $type, $matches)) {
			self::$errors[] = "{$type} is not recognised as an SQL type";
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
		
		foreach ($col as $name=>$def) {
			if (!array_key_exists($name, $res)) {
				$errors[] = "{$name} is not a valid column definition";
				unset($col[$name]);
				continue;
			}
			
			if ($name == "type") {
				self::splitTypeDef($res, $def);
			} else {
				$res[$name] = $def;
			}
		}
		
		return $res;
	}
	
	private static function getTableColumnDef($tblName, $colName, $col) {
		if ($col['type'] != "key") {
			self::$tables[$tblName]["columns"][$colName] = self::getColumnDef($colName, $col);
			return;
		}
		
		// Copy columns from foreign primary key.
		self::getTableDef($colName, true);
		$primaries = self::primaryColumns($colName);
		
		if (!$primaries) {
			return;
		}
		
		$locals = array();
		foreach ($primaries as $primary) {
			$local = "{$colName}_{$primary}";
			$locals[$local] = $primary;
			self::$tables[$tblName]["columns"][$local] = self::$tables[$colName]["columns"][$primary];
			self::$tables[$tblName]["columns"][$local]["primary"] = false;
			self::$tables[$tblName]["columns"][$local]["increment"] = false;
		}
		
		// Set foreign key constraints.
		self::$tables[$tblName]["keys"][$colName] = array(
			"name" => "FK__{$colName}",
			"columns" => $locals,
			"references" => $colName,
			"update" => $col['update'],
			"delete" => $col['delete']
		);
	}
	
	private static function getIndexDef($tblName, $idxName, $index) {
		$unique = false;
		
		if (preg_match("/^unique /", $idxName)) {
			$idxName = substr($idxName, 7);
			$unique = true;
		}
		
		self::$tables[$tblName]['index'][$idxName] = array(
			"unique" => $unique,
			"columns" => $index
		);
	}
	
	private static function getFetchTriggerDef($tblName, $colName, $expr) {
		$expr = $expr["fetch"];
		$matches = array();
		
		preg_match_all("/[\w\.]+/", $expr, $matches, PREG_OFFSET_CAPTURE);
		
		if (!isset($matches[0])) {
			return;
		}
		
		// Replace all dots in column selectors into underscores.
		$expr = preg_replace("/(\w)\.(\w)/", "$1_$2", $expr);
		
		$joins = array();
		$grow = 0;
		// Each match is a table/column selector.
		foreach ($matches[0] as $match) {
			if (strpos($match[0], ".") === false) {
				// Local table selector, add "new."
				$expr = str_insert("new.", $expr, $match[1]+$grow);
				$grow += 4;
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
				
				$last[$pieces[0]] = array(
					$pieces[1] => "var_" . str_replace(".", "_", $match[0])
				);
			}
		}
		
		$expr = "set new.{$colName} = " . $expr . ";";
		
		self::$triggers[$tblName]["fetch"] = array_merge_recursive(
			coal(self::$triggers[$tblName]["fetch"], array()),
			$joins
		);
		self::$triggers[$tblName]["set"][] = $expr;
	}
	
	private static function getSumTriggerDef($tblName, $colName, $expr, $type) {
		$pieces = explode(".", $expr[$type]);
		if (count($pieces) != 2) {
			return;
		}
		
		self::$triggers[$pieces[0]]["aggregate"][$tblName][$type][$pieces[1]] = $colName;
	}
	
	private static function getDerivedColumnDef($tblName, $colName, $col) {
		$allowed = array("fetch", "sum", "count");
		
		foreach ($col as $type=>$sql) {
			if (!in_array($type, $allowed)) {
				return;
			}
			
			self::$tables[$tblName]["columns"][$colName] = self::getColumnDef($colName, $col);
			// Derived columns must be nullable so triggers have a chance to affect them. (MySQL Bug).
			self::$tables[$tblName]["columns"][$colName]["nullable"] = true;
			
			if ($type == "fetch") {
				self::getFetchTriggerDef($tblName, $colName, $col);
			} else {
				self::getSumTriggerDef($tblName, $colName, $col, $type);
			}
		}
	}

	private static function getTableDef($tblName, $dependent=false) {
		if (isset(self::$loadState[$tblName])) {
			return;
		}
		
		self::$loadState[$tblName] = self::LS_LOADING;
		
		$allowed = array("columns", "derived", "index");
		
		if ($dependent) {
			self::$tables = array($tblName=>array()) + self::$tables;
		} else {
			self::$tables[$tblName] = array();
		}
		
		foreach (self::$import[$tblName] as $section=>$def) {
			if (!in_array($section, $allowed)) {
				$errors[] = "{$section} is not a valid table section";
				continue;
			}
			
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
		}
		
		self::$loadState[$tblName] = self::LS_DONE;
	}

	private static function columnType($column) {
		$length = "";
		$unsigned = $column['unsigned'] ? " unsigned" : "";
		if ($column['length']) {
			if ($column['scale']) {
				$length = "({$column['length']},{$column['scale']})";
			} else {
				$length = "({$column['length']})";
			}
		}
		return $column['type'] . $length . $unsigned;
	}

	private static function columnSql($name, $column) {
		$out = array();
		$out[] = $name;
		$out[] = self::columnType($column);
		$out[] = $column['nullable'] ? "null" : "not null";
		
		if ($column['default']) {
			$out[] = "default " . $column['default'];
		}
		if ($column['increment']) {
			$out[] = 'auto_increment';
		}
		
		return join(" ", $out);
	}
}

function str_insert($insert, $into, $offset) {
   return substr($into, 0, $offset) . $insert . substr($into, $offset);
}


SchemaInfo::load('test.yml');
$sql = SchemaInfo::generateSql();
//debug($sql);

$trans = new TransactionRaii;
foreach ($sql as $table) {
	WoolDb::exec($table);
}
$trans->success();


$triggers = SchemaInfo::generateTriggerSql();
debug($triggers);
$trans = new TransactionRaii;
foreach ($triggers as $trigger) {
	WoolDb::exec($trigger);
}
$trans->success();
