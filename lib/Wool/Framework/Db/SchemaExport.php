<?php

require_once('Zend/Db/Adapter/Pdo/Mysql.php');

// SQL helpers
$sqlTypes = array(
	'int' => array('char'=>false, 'num'=>true),
	'float' => array('char'=>false, 'num'=>true),
	'varchar' => array('char'=>true, 'num'=>false),
	'text' => array('char'=>true, 'num'=>false)
);

function isCharacterType($type) {
	global $sqlTypes;
	return isset($sqlTypes[$type]) ? $sqlTypes[$type]['char'] : false;
}
function isNumericType($type) {
	global $sqlTypes;
	return isset($sqlTypes[$type]) ? $sqlTypes[$type]['num'] : false;
}

function extractEnumOptions($column) {
	$options = explode(',', substr($column->COLUMN_TYPE, 5, -1));
	foreach ($options as &$option) {
		$option = trim($option, ' \'');
	}
	return $options;
}

function isUnsigned($column) {
	return stripos($column->COLUMN_TYPE, 'unsigned') !== false;
}

function formatDataType($type) {
	if ($type == "float unsigned") {
		return "float";
	}
	if ($type == "double unsigned") {
		return "double";
	}
	return $type;
}

function mkdir_recursive($pathname, $mode)
{
    is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || @mkdir($pathname, $mode);
}

function file_put_contents_mkdir($file, $contents){
	$dir = dirname($file);
	if(!is_dir($dir)) {
		mkdir_recursive($dir, 0777);
	}
	return file_put_contents($file, $contents);
}


function exportSchema($file) {
	$db = new Zend_Db_Adapter_Pdo_Mysql(array(
			'host'     => $GLOBALS['DB_HOST'],
			'username' => $GLOBALS['DB_USERNAME'],
			'password' => $GLOBALS['DB_PASSWORD'],
			'dbname'   => 'information_schema'
	));

	$db->setFetchMode(Zend_Db::FETCH_OBJ);


	$columns = $db->fetchAll(<<<SQL
select *
from COLUMNS c
where c.TABLE_SCHEMA = ?
SQL
	, $GLOBALS['DB_NAME']);

	$schema = array();

	foreach ($columns as $column) {
		$schema[$column->TABLE_NAME][$column->COLUMN_NAME] = array(
			'name' => $column->COLUMN_NAME,
			'default' => $column->COLUMN_DEFAULT,
			'nullable' => $column->IS_NULLABLE == 'YES',
			'type' => formatDataType($column->DATA_TYPE),
			'length' => isCharacterType($column->DATA_TYPE) ? $column->CHARACTER_MAXIMUM_LENGTH : $column->NUMERIC_PRECISION,
			'scale' => $column->NUMERIC_SCALE,
			'primary' => $column->COLUMN_KEY == 'PRI',
			'auto_increment' => $column->EXTRA == 'auto_increment',
			'additional' => false
		);
		
		// For enums include all the options which are useful in validations.
		if ($column->DATA_TYPE == 'enum') {
			$schema[$column->TABLE_NAME][$column->COLUMN_NAME]['options'] = extractEnumOptions($column);
		}
		
		// For numeric types check if they are unsigned.
		if (isNumericType($column->DATA_TYPE)) {
			$schema[$column->TABLE_NAME][$column->COLUMN_NAME]['unsigned'] = isUnsigned($column);
		}
	}
	
	
	// Next export relations
	$keyColumns = $db->fetchAll(<<<SQL
select TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
from KEY_COLUMN_USAGE
where `TABLE_SCHEMA` = ?
	and REFERENCED_TABLE_NAME is not null
	and REFERENCED_COLUMN_NAME is not null
SQL
	, $GLOBALS['DB_NAME']);

	$relations = array();

	foreach ($keyColumns as $column) {
		$schema[$column->TABLE_NAME][$column->COLUMN_NAME]['nullable'] = true;
		$relations[$column->TABLE_NAME][$column->COLUMN_NAME][$column->REFERENCED_TABLE_NAME][$column->REFERENCED_COLUMN_NAME] = true;
	}
	
	return file_put_contents_mkdir($file, "<?php\nWoolTable::\$schema = " . var_export($schema, true) . ";\n\nEvanceTable::\$relations = " . var_export($relations, true) . ";\n");
}
