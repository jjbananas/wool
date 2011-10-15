<?php

// Find the latest time that a file or array of files was modified.
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

function mkdir_recursive($pathname, $mode=0777) {
	is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
	return is_dir($pathname) || @mkdir($pathname, $mode);
}

function file_put_contents_mkdir($file, $contents){
	$dir = dirname($file);
	if(!is_dir($dir)) {
		mkdir_recursive($dir, 0777);
	}
	return file_put_contents($file, $contents);
}

function fileNameOnly($filename) {
	return pathinfo($filename, PATHINFO_FILENAME);
}

function fileExtension($filename) {
	return pathinfo($filename, PATHINFO_EXTENSION);
}

// Basically the same as PHP's copy but will append an auto-incrementing number
// to the end if the file exists. Returns the new path on success or false on
// failure.
function copyUnique($src, $dest) {
	if (!file_exists($dest)) {
		return copy($src, $dest) ? $dest : false;
	}

	$path = dirname($dest) . "/";
	$name = fileNameOnly($dest);
	$ext = "." . fileExtension($dest);

	$pos = strrpos($name, "_");
	$num = 1;

	if ($pos !== false) {
		$num = substr($name, $pos);

		if (is_numeric($num)) {
			$name = substr($name, 0, $pos);
		} else {
			$num = 1;
		}
	}

	$dest = $path . $name . "_" . $num . $ext;

	while (file_exists($dest)) {
		$num++;
		$dest = $path . $name . "_" . $num . $ext;
	}

	return copy($src, $dest) ? $dest : false;
}

// Copy one directory to another recursively, including all files. Existing
// files will be overwritten.
function copyRecursive($source, $dest, $fileMode=0777, $dirMode=0777) {
	if (!is_dir($source)) {
		return false;
	}

	mkdir_recursive($dest, $dirMode);
	$dir = opendir($source);

	while ($file = readdir($dir)) {
		if ($file == "." || $file == "..") {
			continue;
		}

		$s = $source . "/" . $file;
		$d = $dest . "/" . $file;

		if (is_dir($s)) {
			if (!copyRecursive($s,$d, $fileMode, $dirMode)) {
				return false;
			}
		} else {
			if (!copy($s, $d)) {
				return false;
			}

			chmod($d, $fileMode);
		}
	}

	closedir($dir);

	return true;
}
