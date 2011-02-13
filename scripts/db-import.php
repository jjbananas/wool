<?php

require_once('../lib/Wool/Boot.php');
require_once('Wool/Framework/Db/SchemaImport.php');

SchemaImport::load('../db');
Schema::saveToCache();
