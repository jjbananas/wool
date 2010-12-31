<?php

require_once('Zend/Json.php');
require_once('Burn/JSMin.php');
require_once('Burn/CssCompressor.php');

define('SLASH', DIRECTORY_SEPARATOR);

class Burn {
	// Takes a file and returns all files that are referenced within, or the file
	// itself if it is the end of the line.
	private static function expandFileList($filePath) {
		$info = pathinfo($filePath);
		$path = $info['dirname'];
		$filename = $info['filename'];
		$extension = $info['extension'];

		$sourcePath = $GLOBALS['BASE_PATH'] . SLASH . 'public' . SLASH . $path . SLASH . $filename;

		$sourceFile = "{$sourcePath}.{$extension}";
		$confFile = "{$sourcePath}.conf";

		$files = array('conf'=>array($confFile), 'source'=>array(), 'uri'=>array());
		
		// Load files directly that have no config.
		if (!file_exists($confFile)) {
			if (!file_exists($sourceFile)) {
				if (DEVELOPER) {
					trigger_error("Burn attempting to load non-existent file: " . $sourceFile, E_USER_NOTICE);
				}
				return array();
			}
			
			$files['source'][] = $sourceFile;
			$files['uri'][] = $filePath;
		} else {
			$conf = file_get_contents($confFile);
			$conf = Zend_Json::decode($conf);

			foreach ($conf['files'] as $file) {
				$files = array_merge_recursive($files, self::expandFileList($path . '/' . $file));
			}
		}
		
		return $files;
	}
	
	public static function expandDebugFileList($filePath) {
		if (DEVELOPER) {
			$files = self::expandFileList($filePath);
			return $files['uri'];
		} else {
		 return array($filePath);
		}
	}

	public static function start() {
		$path = $_GET['path'];
		$filename = $_GET['file'];
		$extension = $_GET['ext'];
		
		$cachedPath = $GLOBALS['BASE_PATH'] . SLASH . 'var' . SLASH . 'burn' . SLASH . $path . SLASH . $filename;
		$debugFile = "{$cachedPath}.{$extension}";
		$minFile = "{$cachedPath}.min.{$extension}";
	
		$files = self::expandFileList($path . '/' . $filename . '.' . $extension);
		if (!$files) {
			header("HTTP/1.1 404 Not Found", true, 404);
			exit;
		}
		
		$lastModifiedSource = lastModifiedTime(array_merge($files['conf'], $files['source']));

		// Output
		if ($extension == 'css') {
			header("Content-type: text/css");
		} else {
			header("Content-type: application/x-javascript");
		}

		$modifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : -1;

		if ($lastModifiedSource <= $modifiedSince) {
			header("HTTP/1.1 304 Not Modified", true, 304);
			exit;
		}
		
		if (DEVELOPER) {
			$output = self::updateDebug($debugFile, $lastModifiedSource, $files['source']);
			header("Last-modified: " . gmdate("D, d M Y H:i:s",lastModifiedTime($debugFile)) . " GMT"); 
			echo $output;
		} else {
			$output = self::updateMinified($minFile, $lastModifiedSource, $files['source'], $extension);
			header("Last-modified: " . gmdate("D, d M Y H:i:s",lastModifiedTime($minFile)) . " GMT"); 
			echo $output;
		}
	}
	
	private static function minify($files, $extension) {
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

	private static function joinDebugFiles($files) {
		$debug = array();

		foreach ($files as $file) {
			$debug[] = file_get_contents($file);
			$debug[] = '';
		}

		return join("\r\n", $debug);
	}

	private static function updateMinified($minFile, $lastModifiedSource, $files, $extension) {
		$min = '';
		if (lastModifiedTime($minFile) < $lastModifiedSource) {
			// We need to recache.
			$min = self::minify($files, $extension);
			if (!$min) {
				$min = self::joinDebugFiles($files);
			}
			file_put_contents_mkdir($minFile, $min);
		} else {
			$min = file_get_contents($minFile);
		}
		return $min;
	}

	private static function updateDebug($debugFile, $lastModifiedSource, $files) {
		$debug = '';
		if (lastModifiedTime($debugFile) < $lastModifiedSource) {
			// We need to recreate debug cache.
			$debug = self::joinDebugFiles($files);
			file_put_contents_mkdir($debugFile, $debug);
		} else {
			$debug = file_get_contents($debugFile);
		}
		return $debug;
	}
}
