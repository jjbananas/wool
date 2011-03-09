<?php

require_once('spyc/spyc.php');
require_once('Zend/Db/Adapter/Pdo/Mysql.php');
require_once('Wool/Framework/Db/SqlTypes.php');
require_once('Wool/Framework/Db/Schema.php');

//WoolTable::exportYaml($GLOBALS['BASE_PATH'] . '/db/export/');


class SchemaImport {
	private static $import = array();
	private static $triggers = array();
	
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
			} else {
				self::getTableDef($name);
			}
		}
	}
	
	public static function primaryColumns($schema, $table) {
		if (!isset($schema[$table])) {
			return array();
		}
		
		$primaries = array();
		foreach ($schema[$table]["columns"] as $colName=>$col) {
			if ($col['primary']) {
				$primaries[] = $colName;
			}
		}
		return $primaries;
	}
	
	public static function generateSql() {
		$old = Schema::cachedSchema();
		$new = Schema::fullSchema();
		
		$sql = array();
		
		foreach ($new as $name=>$table) {
			$newTable = !isset($old[$name]);
			
			$out = '';
			$lines = array();
			foreach ($table["columns"] as $colName=>$column) {
				if ($newTable) {
					$update = null;
				} else {
					$update = isset($old[$name]["columns"][$colName]) ? "change" : "add";
					if ($update && $column == $old[$name]["columns"][$colName]) {
						continue;
					}
				}
				$lines[] = self::columnSql($colName, $column, $update);
			}
			
			$primary = Schema::primaryColumns($name);
			$add = '';
			
			if (!$newTable) {
				if ($primary != self::primaryColumns($old, $name)) {
					$lines[] = "drop primary key";
					$add = "add ";
				}
			}
			
			$primary = join(", ", $primary);
			if ($primary && ($newTable || $add)) {
				$lines[] = "{$add}primary key ({$primary})";
			}
			
			
			// Indices
			$oldIndices = isset($old[$name]["index"]) ? $old[$name]["index"] : array();
			
			foreach ($oldIndices as $idxName=>$index) {
				if (!isset($new[$name]["index"][$idxName]) || $new[$name]["index"][$idxName] != $index) {
					$lines[] = "drop index {$idxName}";
				}
			}
			
			$indices = isset($new[$name]["index"]) ? $new[$name]["index"] : array();
			foreach ($indices as $idxName=>$index) {
				$indexSql = array();
				
				if (isset($old[$name]["index"][$idxName]) && $old[$name]["index"][$idxName] == $index) {
					continue;
				}
				
				if (!$newTable) {
					$indexSql[] = "add";
				}
				if ($index["unique"]) {
					$indexSql[] = "unique";
				}
				$indexSql[] = "index";
				$indexSql[] = $idxName;
				$indexSql[] = "(" . join(", ", $index["columns"]) . ")";
				$lines[] = join(" ", $indexSql);
			}
			
			
			// Keys
			$oldKeys = isset($old[$name]["keys"]) ? $old[$name]["keys"] : array();
			
			foreach ($oldKeys as $key) {
				if (!isset($new[$name]["keys"][$key['references']]) || $new[$name]["keys"][$key['references']] != $key) {
					$lines[] = "drop foreign key {$key['name']}";
				}
			}
			
			
			$keys = isset($new[$name]["keys"]) ? $new[$name]["keys"] : array();
			foreach ($keys as $key) {
				if (isset($old[$name]["keys"][$key['references']]) && $old[$name]["keys"][$key['references']] == $key) {
					continue;
				}
				
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
			
			if (!$lines) {
				continue;
			}
			
			// Table
			$create = $newTable ? "create" : "alter";
			
			$out .= "{$create} table `{$name}` (\n";
			$out .= join(",\n", $lines);
			$out .= "\n)\n";
			
			if ($newTable) {
				$out .= "collate='utf8_general_ci'\n";
				$out .= "engine=InnoDB;\n";
			} else {
				$out .= ";\n";
			}
			
			$sql[] = $out;
		}
		
		return $sql;
	}
	
	private static function generateFetchSql(&$declares, &$selects, &$joins, $table, $refTbl, $fetch) {
		$joins[] = "join {$refTbl} on " . Schema::keyCondition($table, $refTbl, $table, $refTbl);
		
		foreach ($fetch as $column=>$var) {
			if (is_string($var)) {
				// Add declare
				$varType = self::columnType(Schema::column($refTbl, $column));
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
			
			$condition = Schema::keyCondition($tblName, $refTbl, "new", $refTbl);
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
		$primaries = Schema::primaryColumns($tblName);
		
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
			
			$condition["ins"] = Schema::keyCondition($tblName, $refTbl, "new", "t");
			$condition["upd"] = Schema::keyCondition($tblName, $refTbl, "new", "t");
			$condition["del"] = Schema::keyCondition($tblName, $refTbl, "old", "t");
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
					$triggerBody = <<<SQL
BEGIN
{$sql}
END
SQL;
					if (self::compareExistingTrigger($triggerTime, $triggerTypes[$type], $tblName, $triggerBody)) {
						continue;
					}
					
					$triggers[] = "DROP TRIGGER IF EXISTS `t{$wi}{$ti}_{$tblName}`;";
					
					$sql = <<<SQL
CREATE TRIGGER `t{$wi}{$ti}_{$tblName}`
{$triggerTime} {$triggerTypes[$type]} ON `{$tblName}`
FOR EACH ROW
{$triggerBody};
SQL;
					$triggers[] = $sql;
				}
			}
		}
		
		return $triggers;
	}
	
	private static function compareExistingTrigger($time, $event, $tblName, $trigger) {
		$db = new Zend_Db_Adapter_Pdo_Mysql(array(
				'host'     => $GLOBALS['DB_HOST'],
				'username' => $GLOBALS['DB_USERNAME'],
				'password' => $GLOBALS['DB_PASSWORD'],
				'dbname'   => 'information_schema'
		));

		$db->setFetchMode(Zend_Db::FETCH_OBJ);
		
		$current = $db->fetchOne(<<<SQL
select ACTION_STATEMENT
from TRIGGERS t
where
	t.TRIGGER_SCHEMA = ?
	and t.ACTION_TIMING = ?
	and t.EVENT_MANIPULATION = ?
	and t.EVENT_OBJECT_TABLE = ?
SQL
		, array($GLOBALS['DB_NAME'], $time, $event, $tblName));
		
		return $current == $trigger;
	}

	// Split SQL types. These can be in three forms: text, int(10), decimal(10,2)
	private static function splitTypeDef(&$result, $type) {
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
		self::$triggers[$tblName]["history"][$colName] = $def;
	}
	
	private static function getTableColumnDef($tblName, $colName, $col) {
		if ($col['type'] != "key") {
			Schema::addColumn($tblName, $colName, self::getColumnDef($colName, $col));
			
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
			$colDef["primary"] = false;
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
		
		$colDef = self::mergeColumnDef(
			Schema::column($pieces[0], $pieces[1]),
			$colName,
			$expr
		);
		$colDef["default"] = "0.00";
		Schema::addColumn($tblName, $colName, $colDef);
		
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

	private static function getTableDef($tblName, $dependent=false) {
		if (isset(self::$loadState[$tblName])) {
			return;
		}
		
		self::$loadState[$tblName] = self::LS_LOADING;
		
		$allowed = array("columns", "derived", "index");
		
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
					Schema::addInfo($tblName, $name, $col);
				}
			}
			else {
				trigger_error("{$section} is not a valid table section", E_USER_WARNING);
			}
		}
		
		self::checkTableRequirements($tblName);
		
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

	private static function columnSql($name, $column, $update=null) {
		$out = array();
		
		if ($update) {
			$out[] = "{$update} column";
			$out[] = $name;
		}
		
		$out[] = $name;
		$out[] = self::columnType($column);
		$out[] = $column['nullable'] ? "null" : "not null";
		
		if ($column['default']) {
			$out[] = "default '" . $column['default'] . "'";
		}
		if ($column['increment']) {
			$out[] = 'auto_increment';
		}
		
		return join(" ", $out);
	}
}
