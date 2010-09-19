<?php

require_once('../lib/Shaded/Boot.php');
require_once('Shaded/Db/SchemaExport.php');

if (exportSchema($GLOBALS['BASE_PATH']  . "/var/database/schema.php")) {
	echo "Success!";
} else {
	echo "Failure!";
}
