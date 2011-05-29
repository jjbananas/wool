<?php

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

function fileExtension($filename) {
	return pathinfo($filename, PATHINFO_EXTENSION);
}
