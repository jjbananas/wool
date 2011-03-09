<?php

require_once('../lib/Wool/Boot.php');
require_once('Wool/Common/include.php');
require_once('Wool/Framework/Db/SchemaImport.php');

SchemaImport::load('../db');
//debug(SchemaImport::generateTriggerSql(),1);
//debug(SchemaImport::generateSql(),1);
//Schema::debug();

$db = new Zend_Db_Adapter_Pdo_Mysql(array(
		'host'     => $GLOBALS['DB_HOST'],
		'username' => $GLOBALS['DB_USERNAME'],
		'password' => $GLOBALS['DB_PASSWORD'],
		'dbname'   => $GLOBALS['DB_NAME']
));

$db->exec("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;");
$sql = SchemaImport::generateSql();
debug($sql);
foreach ($sql as $table) {
	$db->exec($table);
}

$triggers = SchemaImport::generateTriggerSql();
debug($triggers);
foreach ($triggers as $trigger) {
	$db->exec($trigger);
}
$db->exec("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;");

Schema::saveToCache();
