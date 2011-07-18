<?php

require_once('../lib/Wool/Boot.php');
require_once('Wool/Common/include.php');
require_once('Wool/Request.php');
require_once('Wool/Framework/Db.php');
require_once('Wool/Framework/Db/SchemaImport.php');

SchemaImport::load('../db');

WoolDb::connect($GLOBALS['DB_HOST'], $GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD']);

$sql = ImportMySql::generateSql();
debug($sql);

$triggers = ImportMySql::generateTriggerSql();
debug($triggers);

if (Request::isPost()) {
	WoolDb::exec("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;");

	foreach ($sql as $table) {
		WoolDb::exec($table);
	}
	
	foreach ($triggers as $trigger) {
		WoolDb::exec($trigger);
	}
	
	WoolDb::exec("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;");
	
	Schema::saveToCache();
	
	redirectTo(Request::uri());
}
?>
<form method="post">
	<input type="submit" value="Apply Updates" />
</form>
