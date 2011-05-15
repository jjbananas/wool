<?php

require_once('Wool/Common/ImageCompositor.php');

class ImageController extends AppController {
	function adminIndex() {
		$this->addHelper("grid");
		
		$this->images = new WoolGrid("images", "select * from image");
		$this->images->setPerPage(25);
	}
	
	function adminUpload() {
		if (Request::isPost()) {
			$response = array();
			
			$images = param('image', array());
			$images = (isset($images[0]) && is_array($images[0])) ? $images : array($images);
			
			foreach ($images as $num=>$params) {
				if (isset($params["id"])) {
					$file = $this->locateSourceFile($params["id"]);
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
		$json = array("result"=>"success");
		$json["files"] = $this->uploadSourceFiles();
		$this->renderJson($json);
	}
	
	private function uploadSourceFiles($genId=false) {
		$path = publicPath("/uploads/images/source");
		
		$files = array();
		
		foreach ($_FILES as $file) {
			$sha = sha1_file($file["tmp_name"]);
			$shaPath = $path . "/" . str_insert("/", substr($sha, 0, 4), 2);
			
			mkdir_recursive($shaPath);
			move_uploaded_file($file["tmp_name"], $shaPath . "/" . $sha . '-' . $file["name"]);
			$files[$file["name"]] = $sha;
		}
		
		return $files;
	}
	
	private function locateSourceFile($id) {
		$path = publicPath("/uploads/images/source");
		$files = glob($path . "/" . $id . "*.*");
		
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
