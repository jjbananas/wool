<?php
/*
	Configuration shared between all deployments.
	
	Anything in this file will overwrite the deployment config. So if you move
	something from here to the deployment specific config, make sure to copy it
	to all configs and remove it from here.
*/

define('SSL_PORT', 443);

	
// These should be moved to an editable database at some point
$GLOBALS['DATE_FORMAT_SHORT'] = 'd/m/Y';
$GLOBALS['DATE_FORMAT_LONG'] = 'D jS F Y';
$GLOBALS['DATE_TIME_FORMAT'] = 'd/m/Y  H:i:s';
$GLOBALS['DB_DATE_TIME_FORMAT'] = 'Y-m-d H:i:s';
$GLOBALS['DATE_TIME_FORMAT_LONG'] = 'D jS M Y  H:i:s';
$GLOBALS['SYSTEM_CLOCK'] = "GMT";
$GLOBALS['SERVER_CLOCK'] = "GMT";


// Logging emails are sent to the following address from the live site.
$GLOBALS['EMAIL_DEVELOPER_LOG'] = 'tony@tonymarklove.net';

$GLOBALS['DB_CHARACTER_SET'] = 'utf8';
