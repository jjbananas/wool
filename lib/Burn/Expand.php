<?php

require_once('Zend/Json.php');

function expandBurnConfigFiles($origFile) {
	if (!DEVELOPER) {
		return array($origFile);
	}
	
	$info = pathinfo($origFile);
	$info['dirname'] = ($info['dirname'] == '.' ? '' : $info['dirname']);
	$file = $GLOBALS['BASE_PATH'] . $info['dirname'] . '/' . $info['filename'] . '.conf';
	
	// Load files directly that have no config.
	if (!file_exists($file)) {
		return array($origFile);
	}
	
	$conf = file_get_contents($file);
	$conf = Zend_Json::decode($conf);
	
	$files = array();
	
	foreach ($conf['files'] as $file) {
		$files[] = $info['dirname'] . '/' . $file;
	}
	
	return $files;
}
