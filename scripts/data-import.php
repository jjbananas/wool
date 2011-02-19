<?php

require_once('../lib/Wool/Application.php');
require_once('spyc/spyc.php');

$dir = basePath("/db/data/");

$names = glob($dir . "/*.yml");

foreach ($names as $name) {
	$table = basename($name, ".yml");
	$import = Spyc::YAMLLoad($name);

	$imported = new RowSet("select id, data, uniqueId from schema_data");
	$unique = Schema::uniqueColumn($table);
	$importedData = new RowSet("select * from {$table} where {$unique} in :ids", array("ids"=>pluck($imported, "uniqueId")));

	foreach ($import as $key=>$item) {
		if (!is_int($key)) {
			continue;
		}
		
		$id = '';
		$dataHash = '';
		
		foreach ($import["primary"] as $primary) {
			$id .= $item[$primary];
		}
		$id = sha1($id, true);
		
		foreach ($item as $column=>$value) {
			if (in_array($column, $import["ignoreChanged"])) {
				continue;
			}
			
			$dataHash .= $value;
		}
		$dataHash = sha1($dataHash, true);
		
		$log = $imported->by("id", $id);
		if ($log && $log->data == $dataHash) {
			// Import data hasn't changed since previous import.
			continue;
		}
		
		if (!$log) {
			$log = WoolTable::blank("schema_data");
			$row = WoolTable::blank($table);
			
			$log->id = $id;
			$log->data = $dataHash;
		}
		else {
			$row = $importedData->by($unique, $log->uniqueId);
			
			if (!$row) {
				continue;
			}
		}
		
		foreach ($item as $column=>$value) {
			if (in_array($column, $import["ignoreChanged"])) {
				continue;
			}
			
			$row->$column = $value;
		}
		
		WoolTable::save($row);
		$log->uniqueId = $row->$unique;
		WoolTable::save($log);
	}
}
