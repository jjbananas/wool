<?php

ini_set("display_errors", true);
error_reporting(E_ALL);

class Installer {
	private static $extensions = array(
		"bcmath" => "Arbitrary Precision Math",
		"json" => "JSON Encode/Decode",
		"mcrypt" => "Encryption",
		"pcre" => "Perl Regular Expressions",
		"PDO" => "PHP Data Object Database Connector",
		"pdo_mysql" => "MySQL for PDO",
		"curl" => "CURL",
		"imagick" => "Image Magick"
	);

	private static $directories = array(
		"var",
		"var/database",
		"public",
		"public/uploads"
	);

	private static $view = array();
	private static $pageContent = "";

	public static function page1() {
		self::$view["extensions"] = self::$extensions;
	}

	// Check writable directories.
	public static function page2() {
		$baseDir = dirname(dirname(__FILE__));

		self::$view["dirs"] = array();

		foreach (self::$directories as $dir) {
			$path = $baseDir . "/" . $dir;
			self::$view["dirs"][$dir] = is_dir($path) && is_writable($path);
		}
	}

	// Instructions for setting up the CRON job.
	public static function page3() {
		self::$view["command"] = "php " . dirname(dirname(__FILE__)) . "/scripts/cron.php";
	}

	public static function page4() {
	}

	public static function dispatch() {
		$page = 1;

		if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
			$page = $_REQUEST['page'];
		}

		$actionFunc = "page" . $page;

		if (!is_callable(array("self", $actionFunc))) {
			trigger_error("Trying to access non-existent page of installation process!");
			exit;
		}

		self::$view["page"] = $page;

		call_user_func(array("self", $actionFunc));
		self::renderToString($actionFunc, self::$view);
	}

	public static function redirectTo($url) {
		header(sprintf('Location: %1$s', $url));
		exit;
	}

	public static function content() {
		return self::$pageContent;
	}

	private static function renderToString($renderedView, $viewVars=array()) {
		extract($viewVars);
		
		ob_start();
		require("views/{$renderedView}.php");
		self::$pageContent = ob_get_clean();
	}
}
