<?php

require_once('Wool/Common/ImageCompositor.php');

class ImageController extends AppController {
	function thumbnail() {
		$uri = param('uri');
		
		list($params, $name) = $this->splitImageUri($uri);
		
		$srcPath = publicPath("/uploads/images") . $name;
		$cachePath = varPath("/images/" . urlencode($uri));
		
		if (
			!file_exists($cachePath)
			|| !file_exists($srcPath)
			|| (filemtime($srcPath) > filemtime($cachePath))
		)
		{
			$params = $this->processParams($params);
			
			if (isset($params["ds"])) {
				if (isset($params["max"]["w"])) {
					$params["max"]["w"] -= 12;
				}
				if (isset($params["max"]["h"])) {
					$params["max"]["h"] -= 12;
				}
			}
			
			$image = new Imagick($srcPath);
			list($w, $h) = $this->calcFinalSize($image->getImageWidth(), $image->getImageHeight(), $params);
			$image->scaleImage($w, $h);
			
			if (isset($params["ds"])) {
				$shadowLayer = $image->clone();
				$shadowLayer->setImageBackgroundColor("black");
				$shadowLayer->shadowImage(80, 3, 5, 5);
				$shadowLayer->compositeImage($image, Imagick::COMPOSITE_OVER, 0, 0);
				$image = $shadowLayer;
			}

			$image->writeImage($cachePath);
		}
		
		header("Content-Type: image/png");
		readfile($cachePath);
		$this->stopRender();
	}
	
	function adminIndex() {
		$this->addHelper("grid");
		
		$this->images = new WoolGrid("images", "select * from image");
		$this->images->setPerPage(25);
	}
	
	private function splitImageUri($uri) {
		return explode("__", $uri, 2);
	}
	
	private function processParams($params) {
		$params = $this->splitParams($params);
		$out = array();
		
		foreach ($params as $param=>$options) {
			if ($param == "sx") {
				$out["max"]["w"] = $options[0];
			}
			if ($param == "sy") {
				$out["max"]["h"] = $options[0];
			}
			if ($param == "ds") {
				$out["ds"] = true;
			}
		}
		
		return $out;
	}
	
	private function splitParams($params) {
		$splits = explode("_", $params);
		$params = array();
		
		foreach ($splits as $split) {
			$param = explode("-", $split);
			if (count($param) == 1) {
				$params[$param[0]] = true;
			} else {
				$params[$param[0]] = explode(",", $param[1]);
			}
		}
		
		return $params;
	}
	
	private function calcFinalSize($w, $h, $params=array()) {
		$reqW = $w;
		$reqH = $h;
		
		$aspect = isset($params["aspect"]) ? $params["aspect"] : $w/$h;
		
		if (isset($params["min"]["w"]) && $w < $params["min"]["w"]) {
			$w = $params["min"]["w"];
		}
		$h = $w / $aspect;
		
		if (isset($params["min"]["h"]) && $h < $params["min"]["h"]) {
			$h = $params["min"]["h"];
		}
		$w = $h * $aspect;
		
		if (isset($params["max"]["w"]) && $w > $params["max"]["w"]) {
			$w = $params["max"]["w"];
		}
		$h = $w / $aspect;

		if (isset($params["max"]["h"]) && $h > $params["max"]["h"]) {
			$h = $params["max"]["h"];
		}
		$w = $h * $aspect;
		
		return array(round($w), round($h));
	}
	
	function adminUpload() {
		$this->imageParams = array(
			"min" => array(
				"w" => 100,
				"h" => 100
			),
			"max" => array(
				"w" => 200,
				"h" => 150
			),
			"aspect" => 640/480
		);
		
		if (Request::isPost()) {
			$response = array();
			
			$images = param('image', array());
			$images = (isset($images[0]) && is_array($images[0])) ? $images : array($images);
			
			foreach ($images as $num=>$params) {
				if (isset($params["hash"])) {
					$file = $this->locateSourceFile($params["hash"]);
				} else {
					
				}
				
				if (!$file) {
					$response[$num] = "No image uploaded";
					continue;
				}
				
				$savePath = publicPath("/uploads/images/") . date('Y/m/');
				mkdir_recursive($savePath);
				$savePath .= basename($file);
				
				$image = new Imagick($file);
				$image->cropImage($params["w"], $params["h"], $params["x"], $params["y"]);
				
				list($w, $h) = $this->calcFinalSize($image->getImageWidth(), $image->getImageHeight(), $this->imageParams);
				$image->scaleImage($w, $h);
				$image->writeImage($savePath);
				
				$dbImage = WoolTable::fetch("image", null, "image");
				$dbImage->title = $params["title"];
				$dbImage->file = date('/Y/m/') . basename($file);
				WoolTable::save($dbImage);
			}
			
			$response["errors"] = WoolErrors::get();
			
			$this->renderJson($response);
		}
	}
	
	function adminPreSave() {
		$json = array("success"=>true);
		$json["files"] = $this->uploadSourceFiles();
		$this->renderJson($json);
	}
	
	private function uploadSourceFiles($genId=false) {
		$path = publicPath("/uploads/images/source");
		
		$files = array();
		
		foreach ($_FILES as $id=>$file) {
			$id = substr($id, 6);
			$sha = sha1_file($file["tmp_name"]);
			$shaPath = $path . "/" . str_insert("/", substr($sha, 0, 4), 2);
			
			mkdir_recursive($shaPath);
			move_uploaded_file($file["tmp_name"], $shaPath . "/" . substr($sha, 4) . "." . fileExtension($file["name"]));
			$files[$id] = $sha;
		}
		
		return $files;
	}
	
	private function locateSourceFile($hash) {
		$path = publicPath("/uploads/images/source");
		$shaPath = $path . "/" . str_insert("/", substr($hash, 0, 4), 2);
		$files = glob($shaPath . "/" . substr($hash, 4) . ".*");
		
		if (!$files || count($files) != 1) {
			return null;
		}
		
		return $files[0];
	}
	
	
	public function adminCreate() {
		$path = publicPath("/image-create/");
		
		$creator = new ImageCompositor($path . "image.yml", array("variables"=>array("foreground"=>"#00ff00")));
		$this->stopRender();
	}
}
