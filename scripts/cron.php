<?php

require_once(dirname(__FILE__) . "/../lib/Wool/Boot.php");
require_once("Wool/Common/functions.php");
require_once("Wool/Framework/functions.php");
require_once("Wool/Framework/Db.php");
require_once("Wool/Core/Cron.php");

WoolDb::connect($GLOBALS['DB_HOST'], $GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD']);

WoolCron::runAllCrons();