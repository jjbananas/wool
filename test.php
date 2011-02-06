<?php

require_once('./lib/Wool/Boot.php');
require_once('./lib/spyc/spyc.php');
require_once('Zend/Db/Adapter/Pdo/Mysql.php');
require_once('./lib/Wool/Framework/Db/SqlTypes.php');

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
			"name" => "",
			"type" => "int",
			"length" => 0,
			"scale" => 0,
			"default" => null,
			"nullable" => false,
			"primary" => false,
			"increment" => false,
			"unsigned" => false,
			"derived" => false
		)
	);

	
	public static function load($name) {
		self::$import = Spyc::YAMLLoad($name);

		foreach (self::$import as $name=>$entry) {
			if (preg_match("/^column /", $name)) {
				$column = substr($name, 7); 
				self::$columnTypes[$column] = self::getColumnDef($column, $entry);
			} else {
				self::getTableDef($name);
			}
		}
		
		return file_put_contents_mkdir($GLOBALS['BASE_PATH'] . '/var/database/schema.php', "<?php\nreturn " . var_export(self::$tables, true) . ";\n");
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
		$joins[] = "join {$refTbl} on " . self::keyCondition($table, $refTbl, $table, $refTbl);
		
		foreach ($fetch as $column=>$var) {
			if (is_string($var)) {
				// Add declare
				$varType = self::columnType(self::$tables[$refTbl]["columns"][$column]);
				$declares[] = "declare {$var} {$varType};";
				$selects["{$refTbl}.{$column}"] = $var;
			} else {
				// Deeper join so recurse.
				self::generateFetchSql($declares, $selects, $joins, $refTbl, $column, $var);
			}
		}
	}
	
	private static function createFetchTrigger($tblName, $deps) {
		$declares = array();
		$sqls = array();
		
		foreach ($deps as $refTbl=>$fetch) {
			$selects = array();
			$joins = array();
			
			self::generateFetchSql($declares, $selects, $joins, $tblName, $refTbl, $fetch);
			array_shift($joins);
			
			$condition = self::keyCondition($tblName, $refTbl, "new", $refTbl);
			$select = join(", ", array_keys($selects));
			$into = join(", ", $selects);
			$joins = join("\n", $joins);

			$sqls[] = <<<SQL
select
{$select}
into
{$into}
from {$refTbl}
{$joins}
where {$condition};
SQL;
		}
		
		if (!$declares && !$sqls) {
			return array();
		}
		
		$declares = join("\n", $declares);
		$sqls = join("\n\n", $sqls);
		$sql = array();
		
		foreach (array("ins", "upd") as $type) {
			$sql[$type] = <<<SQL
{$declares}

{$sqls}
SQL;
		}
		
		return $sql;
	}
	
	private static function createHistoryTrigger($tblName, $cols) {
		$heads = array();
		$ins = array();
		$upd = array();
		$del = array();
		
		// Add primary key columns.
		$primaries = self::primaryColumns($tblName);
		
		foreach ($primaries as $primary) {
			$heads[] = $primary;
			$ins[] = "new.{$primary}";
			$upd[] = "new.{$primary}";
			$del[] = "old.{$primary}";
		}

		// Add standard history columns.
		$heads[] = "cause";
		$ins[] = "'ins'";
		$upd[] = "'upd'";
		$del[] = "'del'";
		
		$heads[] = "changedOn";
		$ins[] = "now()";
		$upd[] = "now()";
		$del[] = "now()";
		
		foreach ($cols as $colName=>$col) {
			foreach ($col as $type) {
				$heads[] = "{$type}_{$colName}";
				
				if ($type == "diff") {
					$ins[] = "new.{$colName}";
					$upd[] = "new.{$colName} - old.{$colName}";
					$del[] = "-old.{$colName}";
				}
				else if ($type == "old") {
					$ins[] = "null";
					$upd[] = "old.{$colName}";
					$del[] = "old.{$colName}";
				}
				else if ($type == "new") {
					$ins[] = "new.{$colName}";
					$upd[] = "new.{$colName}";
					$del[] = "null";
				}
			}
		}
		
		$heads = join(", ", $heads);
		$ins = join(", ", $ins);
		$upd = join(", ", $upd);
		$del = join(", ", $del);
		
		$sql = array();
		
		foreach (array("ins", "upd", "del") as $type) {
			$sql[$type] = <<<SQL
insert into `history_{$tblName}`
({$heads})
values
({$$type});
SQL;
		}
		
		return $sql;
	}
	
	private static function createAggregateTrigger($tblName, $aggregates) {
		$sqls = array();
	
		foreach ($aggregates as $refTbl=>$ag) {
			$ins = array();
			$upd = array();
			$del = array();
			
			foreach ($ag as $type=>$group) {
				foreach ($group as $local=>$foreign) {
					if ($type == "sum") {
						$ins[] = "t.{$foreign} = t.{$foreign} + new.{$local}";
						$upd[] = "t.{$foreign} = t.{$foreign} - old.{$local} + new.{$local}";
						$del[] = "t.{$foreign} = t.{$foreign} - old.{$local}";
					} else if ($type == "count") {
						$ins[] = "t.{$foreign} = t.{$foreign} + 1";
						$del[] = "t.{$foreign} = t.{$foreign} - 1";
					}
				}
			}
			
			$condition["ins"] = self::keyCondition($tblName, $refTbl, "new", "t");
			$condition["upd"] = self::keyCondition($tblName, $refTbl, "new", "t");
			$condition["del"] = self::keyCondition($tblName, $refTbl, "old", "t");
			$ins = join(",\n", $ins);
			$upd = join(",\n", $upd);
			$del = join(",\n", $del);
			
			foreach (array("ins", "upd", "del") as $type) {
				if (!($$type)) { continue; }
				
				$sqls[$type][] = <<<SQL
update {$refTbl} t
set
{$$type}
where {$condition[$type]};
SQL;
			}
		}
		
		$sql = array();
		foreach ($sqls as $type=>$data) {
			$sql[$type] = join("\n\n", $data);
		}
		return $sql;
	}
	
	public static function generateTriggerSql() {
		$triggers = array();
		
		foreach (self::$triggers as $tblName=>$table) {
			$declares = array();
			$sqls = array();
			
			// FETCH automations.
			if (isset($table["fetch"])) {
				$t = self::createFetchTrigger($tblName, $table["fetch"]);
				foreach ($t as $type=>$trigger) {
					$sqls["before"][$type][] = $trigger;
				}
			}
			
			if (isset($table["set"])) {
				$sqls["before"]["ins"][] = join("\n", $table['set']);
				$sqls["before"]["upd"][] = join("\n", $table['set']);
			}
			
			// History tables.
			if (isset($table["history"])) {
				$t = self::createHistoryTrigger($tblName, $table["history"]);
				foreach ($t as $type=>$trigger) {
					$sqls["after"][$type][] = $trigger;
				}
			}
			
			// Aggregate automations
			if (isset($table["aggregate"])) {
				$t = self::createAggregateTrigger($tblName, $table["aggregate"]);
				foreach ($t as $type=>$trigger) {
					$sqls["before"][$type][] = $trigger;
				}
			}
			
			$triggerTypes = array("ins"=>"INSERT", "upd"=>"UPDATE", "del"=>"DELETE");
			
			foreach ($sqls as $triggerTime=>$types) {
				foreach ($types as $type=>$sql) {
					$sql = join("\n\n", $sql);
					$wi = $triggerTime[0];
					$ti = $type[0];
				
					$sql = <<<SQL
CREATE TRIGGER `t{$wi}{$ti}_{$tblName}`
{$triggerTime} {$triggerTypes[$type]} ON `{$tblName}`
FOR EACH ROW BEGIN
{$sql}
END;
SQL;
					$triggers[] = $sql;
				}
			}
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
		
	private static function mergeColumnDef($merge, $colName, $col) {
		foreach ($col as $name=>$def) {
			if ($name != "history" && !array_key_exists($name, $merge)) {
				$errors[] = "{$name} is not a valid column definition";
				unset($col[$name]);
				continue;
			}
			
			if ($name == "type") {
				self::splitTypeDef($merge, $def);
			} else {
				$merge[$name] = $def;
			}
		}
		
		// Column names must always be given explicity. No merge.
		$merge["name"] = isset($col["name"]) ? $col["name"] : ucwords(join(' ', preg_split('/(?=[A-Z])/', $colName)));
		
		return $merge;
	}
	
	private static function baseHistoryTable($tblName) {
		$name = "history_{$tblName}";
		
		if (isset(self::$tables[$name])) {
			return;
		}
		
		// Copy primary columns.
		$primaries = self::primaryColumns($tblName);
		
		foreach ($primaries as $primary) {
			self::$tables[$name]["columns"][$primary] = self::$tables[$tblName]["columns"][$primary];
			self::$tables[$name]["columns"][$primary]["increment"] = false;
		}
		
		// Set up standard history columns.
		self::$tables[$name]["columns"]["changedOn"] = self::getColumnDef("changedOn", array(
			"type" => "datetime",
			"primary" => true
		));
		
		self::$tables[$name]["columns"]["cause"] = self::getColumnDef("cause", array(
			"type" => "enum",
			"length" => array("ins", "upd", "del"),
		));
	}
	
	private static function getHistoryColumnDef($tblName, $colName, $def) {
		$allowed = array("old", "new", "diff");
		$def = array_map("trim", explode("|", $def));
		
		self::baseHistoryTable($tblName);
		
		foreach ($def as $type) {
			if (!in_array($type, $allowed)) {
				self::$errors[] = "Unrecognised history type";
				continue;
			}
			
			$name = "history_{$tblName}";
			$cname = "{$type}_{$colName}";
			self::$tables[$name]["columns"][$cname] = self::$tables[$tblName]["columns"][$colName];
			self::$tables[$name]["columns"][$cname]["nullable"] = true;
			self::$tables[$name]["columns"][$cname]["primary"] = false;
			self::$tables[$name]["columns"][$cname]["increment"] = false;
		}
		
		self::$triggers[$tblName]["history"][$colName] = $def;
	}
	
	private static function getTableColumnDef($tblName, $colName, $col) {
		if ($col['type'] != "key") {
			self::$tables[$tblName]["columns"][$colName] = self::getColumnDef($colName, $col);
			
			if (isset($col['history'])) {
				self::getHistoryColumnDef($tblName, $colName, $col['history']);
			}
			
			return;
		}
		
		// Copy columns from foreign primary key.
		self::getTableDef($colName, true);
		$primaries = self::primaryColumns($colName);
		
		if (!$primaries) {
			return;
		}
		
		$locals = array();
		$prefix = isset($col['prefix']) ? "{$col['prefix']}_" : "";
		foreach ($primaries as $primary) {
			$local = "{$prefix}{$primary}";
			$locals[$local] = $primary;
			self::$tables[$tblName]["columns"][$local] = self::$tables[$colName]["columns"][$primary];
			self::$tables[$tblName]["columns"][$local]["primary"] = false;
			self::$tables[$tblName]["columns"][$local]["increment"] = false;
		}
		
		// Set foreign key constraints.
		self::$tables[$tblName]["keys"][$colName] = array(
			"name" => "FK__{$tblName}_{$colName}",
			"columns" => $locals,
			"references" => $colName,
			"update" => $col['update'],
			"delete" => $col['delete']
		);
		
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
		
		self::$tables[$tblName]['index'][$idxName] = array(
			"unique" => $unique,
			"columns" => $index
		);
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
		self::$tables[$tblName]["columns"][$colName] = self::mergeColumnDef(
			self::$tables[$srcTbl]["columns"][$srcCol],
			$colName,
			$col
		);
		
		$expr = "set new.{$colName} = " . $expr . ";";
		
		self::$triggers[$tblName]["fetch"] = array_merge_recursive(
			isset(self::$triggers[$tblName]["fetch"]) ? self::$triggers[$tblName]["fetch"] : array(),
			$joins
		);
		self::$triggers[$tblName]["set"][] = $expr;
	}
	
	private static function getSumTriggerDef($tblName, $colName, $expr, $type) {
		$pieces = explode(".", $expr[$type]);
		if (count($pieces) != 2) {
			return;
		}
		
		// Create target column.
		self::getTableDef($pieces[0], true);
		
		self::$tables[$tblName]["columns"][$colName] = self::mergeColumnDef(
			self::$tables[$pieces[0]]["columns"][$pieces[1]],
			$colName,
			$expr
		);
		self::$tables[$tblName]["columns"][$colName]["default"] = "0.00";
		
		// Register trigger data.
		self::$triggers[$pieces[0]]["aggregate"][$tblName][$type][$pieces[1]] = $colName;
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
			self::$tables[$tblName]["columns"][$colName]["nullable"] = true;
			self::$tables[$tblName]["columns"][$colName]["derived"] = true;
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
		
		if (!isset(self::$import[$tblName])) {
			trigger_error("'{$tblName}' not found in database dictionary", E_USER_WARNING);
		}
		
		foreach (self::$import[$tblName] as $section=>$def) {
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
					self::$tables[$tblName]["info"][$name] = $col;
				}
			}
			else {
				$errors[] = "{$section} is not a valid table section";
			}
		}
		
		// Give the table a good default name if not provided.
		if (!isset(self::$tables[$tblName]["info"]["name"])) {
			self::$tables[$tblName]["info"]["name"] = ucwords(join(' ', explode('_', $tblName)));
		}
		
		self::$loadState[$tblName] = self::LS_DONE;
	}

	private static function columnType($column) {
		$length = "";
		$unsigned = $column['unsigned'] ? " unsigned" : "";
		if ($column['length']) {
			if (is_array($column['length'])) {
				$vals = "'" . implode("','", $column['length']) . "'";
				$length = "({$vals})";
			} else if ($column['scale']) {
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
/*
$db = new Zend_Db_Adapter_Pdo_Mysql(array(
		'host'     => $GLOBALS['DB_HOST'],
		'username' => $GLOBALS['DB_USERNAME'],
		'password' => $GLOBALS['DB_PASSWORD'],
		'dbname'   => $GLOBALS['DB_NAME']
));

$db->exec("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;");
$sql = SchemaInfo::generateSql();
//debug($sql);
foreach ($sql as $table) {
	$db->exec($table);
}

$triggers = SchemaInfo::generateTriggerSql();
debug($triggers);
foreach ($triggers as $trigger) {
	$db->exec($trigger);
}
$db->exec("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;");
*/
