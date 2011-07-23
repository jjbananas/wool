<?php

class ImportMySql {
	public static $triggers = array();

	// Compares columns from a datbase point of view, ignoring non-database fields.
	private static function compareColumns($col1, $col2) {
		$compare = array("type", "length", "scale", "default", "nullable", "primary", "increment", "unsigned");

		foreach ($compare as $field) {
			if ($col1[$field] != $col2[$field]) {
				return false;
			}
		}

		return true;
	}

	public static function generateSql() {
		$old = Schema::cachedSchema();
		$new = Schema::fullSchema();
		
		$sql = array();
		
		foreach ($new as $name=>$table) {
			$newTable = !isset($old[$name]);
			
			$lines = array();
			
			// Columns
			$oldColumns = isset($old[$name]["columns"]) ? $old[$name]["columns"] : array();
			
			foreach ($oldColumns as $colName=>$column) {
				if (!isset($new[$name]["columns"][$colName])) {
					$lines[] = "drop column {$colName}";
				}
			}
			
			$lastColName = null;

			foreach ($table["columns"] as $colName=>$column) {
				if ($newTable) {
					$update = null;
				} else {
					$update = isset($old[$name]["columns"][$colName]) ? "change" : "add";
					if ($update == "change" && self::compareColumns($column, $old[$name]["columns"][$colName])) {
						$lastColName = $colName;
						continue;
					}
				}
				$lines[] = self::columnSql($colName, $column, $update, ($newTable ? null : $lastColName));
				$lastColName = $colName;
			}
			
			$primary = Schema::primaryColumns($name);
			$add = '';
			
			if (!$newTable) {
				$selfPrimary = self::primaryColumns($old, $name);
				if ($primary != $selfPrimary) {
					if ($selfPrimary) {
						$lines[] = "drop primary key";
					}
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
					"%s constraint `%s` foreign key (%s) references `%s` (%s) on update %s on delete %s",
					$newTable ? "" : "add",
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
			$out = '';
			
			if ($newTable) {
				$out .= "create table `{$name}` (\n";
				$out .= join(",\n", $lines);
				$out .= "\n)\n";
			} else {
				$out .= "alter table `{$name}`\n";
				$out .= join(",\n", $lines);
				$out .= "\n";
			}
			
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
		WoolDb::connect($GLOBALS['DB_HOST'], 'information_schema', $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], 'schema');

		$current = WoolDb::fetchOne(<<<SQL
select ACTION_STATEMENT
from TRIGGERS t
where
	t.TRIGGER_SCHEMA = ?
	and t.ACTION_TIMING = ?
	and t.EVENT_MANIPULATION = ?
	and t.EVENT_OBJECT_TABLE = ?
SQL
		, array($GLOBALS['DB_NAME'], $time, $event, $tblName));
		
		WoolDb::switchConnection("default");
		
		return $current == $trigger;
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

	private static function columnSql($name, $column, $update=null, $after=null) {
		$out = array();
		
		if ($update) {
			$out[] = "{$update} column";
			if ($update == "change") {
				$out[] = $name;
			}
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

		if ($after) {
			$out[] = "after {$after}";
		}
		
		return join(" ", $out);
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
}
