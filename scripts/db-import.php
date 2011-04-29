<?php

require_once('../lib/Wool/Boot.php');
require_once('Wool/Common/include.php');
require_once('Wool/Request.php');
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

$sql = ImportMySql::generateSql();
debug($sql);

$triggers = ImportMySql::generateTriggerSql();
debug($triggers);

if (Request::isPost()) {
	$db->exec("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;");

	foreach ($sql as $table) {
		$db->exec($table);
	}
	
	foreach ($triggers as $trigger) {
		$db->exec($trigger);
	}
	
	$db->exec("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;");
	
	Schema::saveToCache();
	
	redirectTo(Request::uri());
}
?>
<form method="post">
	<input type="submit" value="Apply Updates" />
</form>
