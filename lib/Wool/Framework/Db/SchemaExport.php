<?php

require_once('Wool/Framework/Db/SqlTypes.php');


function formatLength($column) {
	if ($column->DATA_TYPE == 'enum') {
		$options = explode(',', substr($column->COLUMN_TYPE, 5, -1));
		foreach ($options as &$option) {
			$option = trim($option, ' \'');
		}
		return $options;
	}

	if ($column->COLUMN_TYPE == "tinyint(1)") {
		return null;
	}

	return SqlTypes::isText($column->DATA_TYPE) ? $column->CHARACTER_MAXIMUM_LENGTH : $column->NUMERIC_PRECISION;
}

function isUnsigned($column) {
	if (!SqlTypes::isNumeric($column->DATA_TYPE)) {
		return false;
	}
	return stripos($column->COLUMN_TYPE, 'unsigned') !== false;
}

function formatDataType($column) {
	if ($column->DATA_TYPE == "float unsigned") {
		return "float";
	}
	if ($column->DATA_TYPE == "double unsigned") {
		return "double";
	}
	if ($column->COLUMN_TYPE == "tinyint(1)") {
		return "bool";
	}
	return $column->DATA_TYPE;
}

function exportSchema($file) {
	WoolDb::connect($GLOBALS['DB_HOST'], "information_schema", $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD']);

	$columns = WoolDb::fetchAll(<<<SQL
select *
from COLUMNS c
where c.TABLE_SCHEMA = ?
SQL
	, $GLOBALS['DB_NAME']);

	$schema = array();

	foreach ($columns as $column) {
		$schema[$column->TABLE_NAME]["columns"][$column->COLUMN_NAME] = array(
			'name' => $column->COLUMN_NAME,
			'default' => $column->COLUMN_DEFAULT,
			'nullable' => $column->IS_NULLABLE == 'YES',
			'type' => formatDataType($column),
			'length' => formatLength($column),
			'scale' => $column->NUMERIC_SCALE,
			'primary' => $column->COLUMN_KEY == 'PRI',
			'increment' => $column->EXTRA == 'auto_increment',
			'additional' => false,
			'unsigned' => isUnsigned($column)
		);
	}
	

	$indices = WoolDb::fetchAll(<<<SQL
select s.TABLE_NAME, !s.NON_UNIQUE IS_UNIQUE, s.INDEX_NAME, GROUP_CONCAT(s.COLUMN_NAME) COLUMN_NAMES
from STATISTICS s
where s.TABLE_SCHEMA = ? and s.INDEX_NAME != 'PRIMARY' and s.INDEX_NAME not like 'FK%'
group by s.TABLE_NAME, s.INDEX_NAME
SQL
	, $GLOBALS['DB_NAME']);

	foreach ($indices as $index) {
		$schema[$index->TABLE_NAME]["index"][$index->INDEX_NAME] = array(
			"unique" => !!$index->IS_UNIQUE,
			"columns" => explode(",", $index->COLUMN_NAMES)
		);
	}

	
	// Next export relations
	$keyColumns = WoolDb::fetchAll(<<<SQL
select
	kcu.CONSTRAINT_NAME,
	kcu.TABLE_NAME,
	kcu.COLUMN_NAME,
	kcu.REFERENCED_TABLE_NAME,
	kcu.REFERENCED_COLUMN_NAME,
	rc.UPDATE_RULE,
	rc.DELETE_RULE
from KEY_COLUMN_USAGE kcu
join REFERENTIAL_CONSTRAINTS rc on rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
where kcu.TABLE_SCHEMA = ?
	and kcu.REFERENCED_TABLE_NAME is not null
	and kcu.REFERENCED_COLUMN_NAME is not null
SQL
	, $GLOBALS['DB_NAME']);


	foreach ($keyColumns as $column) {
		// $schema[$column->TABLE_NAME]["columns"][$column->COLUMN_NAME]['nullable'] = true;

		$relation = array(
			"name" => $column->CONSTRAINT_NAME,
			"columns" => array($column->COLUMN_NAME=>$column->REFERENCED_COLUMN_NAME),
			"references" => $column->REFERENCED_TABLE_NAME,
			"update" => strtolower($column->UPDATE_RULE),
			"delete" => strtolower($column->DELETE_RULE)
		);

		$schema[$column->TABLE_NAME]["keys"][$column->REFERENCED_TABLE_NAME] = $relation;
	}
	
	return file_put_contents_mkdir($file, "<?php\nreturn " . var_export($schema, true) . ";\n");
}
