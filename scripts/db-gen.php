<?php

require_once('../lib/Wool/Boot.php');
require_once('Wool/Db/SchemaExport.php');

if (exportSchema($GLOBALS['BASE_PATH']  . "/var/database/schema.php")) {
	echo "Success!";
} else {
	echo "Failure!";
}
