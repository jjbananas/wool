<?php

require_once('../Wool/Boot.php');
require_once('Zend/Json.php');
require_once('Burn/JSMin.php');
require_once('Burn/CssCompressor.php');

define('SLASH', DIRECTORY_SEPARATOR);

$sourcePath = $GLOBALS['PUBLIC_PATH'] . SLASH . $_GET['path'] . SLASH . $_GET['file'];
$cachedPath = $GLOBALS['BASE_PATH'] . SLASH . 'var' . SLASH . 'burn' . SLASH . $_GET['path'] . SLASH . $_GET['file'];
$extension = $_GET['ext'];

$sourceFile = "{$sourcePath}.{$extension}";
$confFile = "{$sourcePath}.conf";
$debugFile = "{$cachedPath}.{$extension}";
$minFile = "{$cachedPath}.min.{$extension}";

$files = array();

// Load files directly that have no config.
if (!file_exists($confFile)) {
	if (!file_exists($sourceFile)) {
		header("HTTP/1.1 404 Not Found", true, 404);
		exit;
	}
	
	$files[] = $sourceFile;
} else {
	$conf = file_get_contents($confFile);
	$conf = Zend_Json::decode($conf);

	$files = $conf['files'];
	foreach ($files as &$file) {
		$file = $GLOBALS['PUBLIC_PATH'] . SLASH . $_GET['path'] . SLASH . $file;
	}
}

// Find the latest time that any of the source files was modified.
function lastModifiedTime($files) {
	if (is_string($files)) {
		return file_exists($files) ? filemtime($files) : 0;
	}
	
    $latest = 0;
    foreach ($files as $file) {
        $time = file_exists($file) ? filemtime($file) : 0;
        if ($time > $latest) {
            $latest = $time;
        }
    }
    
    return $latest;
}

$lastModifiedSource = lastModifiedTime(array_merge(array($confFile), $files));

$min = '';
$debug = '';


function minify($files) {
	global $extension;
	
	$min = array();
	foreach ($files as $file) {
		if ($extension == 'css') {
			$min[] = CssCompressor::process(file_get_contents($file));
		} else {
			$min[] = JSMin::minify(file_get_contents($file));
		}
	}
	return join("\r\n", $min);
}

function joinDebugFiles($files) {
	$debug = array();

	foreach ($files as $file) {
		$debug[] = file_get_contents($file);
		$debug[] = '';
	}

	return join("\r\n", $debug);
}

function mkdir_recursive($pathname, $mode)
{
    is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || @mkdir($pathname, $mode);
}

function file_put_contents_mkdir($file, $contents){
	$dir = dirname($file);
	if(!is_dir($dir)) {
		mkdir_recursive($dir, 0777);
	}
	file_put_contents($file, $contents);
}

function updateMinified() {
	global $minFile, $min, $lastModifiedSource, $files;
	if (lastModifiedTime($minFile) < $lastModifiedSource) {
		// We need to recache.
		$min = minify($files);
		if (!$min) {
			$min = joinDebugFiles($files);
		}
		file_put_contents_mkdir($minFile, $min);
	} else {
		$min = file_get_contents($minFile);
	}
}

function updateDebug() {
	global $debugFile, $debug, $lastModifiedSource, $files;
	if (lastModifiedTime($debugFile) < $lastModifiedSource) {
		// We need to recreate debug cache.
		$debug = joinDebugFiles($files);
		file_put_contents_mkdir($debugFile, $debug);
	} else {
		$debug = file_get_contents($debugFile);
	}
}


// Output
if ($extension == 'css') {
	header("Content-type: text/css");
} else {
	header("Content-type: application/x-javascript");
}

$modifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : -1;

if (DEVELOPER) {
	if ($lastModifiedSource <= $modifiedSince) {
		header("HTTP/1.1 304 Not Modified", true, 304);
		exit;
	}
	
	updateDebug();
	header("Last-modified: " . gmdate("D, d M Y H:i:s",lastModifiedTime($debugFile)) . " GMT"); 
	echo $debug;
} else {
	if (lastModifiedTime($minFile) <= $modifiedSince) {
		header("HTTP/1.1 304 Not Modified", true, 304);
		exit;
	}
	
	header("Last-modified: " . gmdate("D, d M Y H:i:s",lastModifiedTime($minFile)) . " GMT"); 
	updateMinified();
	echo $min;
}
