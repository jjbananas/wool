<?php

// Even lower than Application, Boot sets up common code without starting the
// entire framework. All setup is done automatically at the bottom of the file.
class Boot {
	private static $fatalErrorMask = 0;
	
	public static function errorFatal($mask=null) {
		if (!is_null($mask)) {
			self::$fatalErrorMask = $mask;
		}
		return self::$fatalErrorMask;
	}

	public static function startErrorHandling() {
		// Always use our custom error handler. Live site might log errors.
		set_error_handler(array('Boot', 'errorHandler'));
		set_exception_handler(array('Boot', 'exceptionHandler'));
		error_reporting(E_ALL);

		if (DEVELOPER) {
			// Force displaying of errors even on live sites when in DEVELOPER mode.
			ini_set('display_errors', 1);
			// Switch off email logging if we are seeing the errors anyway.
			ini_set('log_errors', 0);
			self::errorFatal(E_ALL);
		} else {
			ini_set('display_errors', 0);
			ini_set('log_errors', 1);
			
			self::errorFatal(E_ALL^E_NOTICE);
		}
	}
	
	private static function renderErrorPage($viewVars=array()) {
		// Clear out all output buffers.
		while (ob_get_level()) {
			ob_end_clean();
		}
		
		extract($viewVars);
		
		ob_start();
		require("views/system/stacktrace.php");
		$body_content = ob_get_clean();
		
		ob_start();
		require("views/layouts/system.php");
		$body_content = ob_get_clean();
		
		echo $body_content;
	}
	
	private static function prepareBacktrace($backtrace, $shift=false) {
		// Remove functions inside the error handler itself.
		if ($shift) {
			array_shift($backtrace);
		}
			
		foreach($backtrace as &$l){
			$argprint = array();

			if(isset($l['args'])){
				foreach ($l['args'] as $arg) {
					$val = is_object($arg) ? "class " . get_class($arg) : strval($arg);
					$argprint[] = $val;
				}
			}

			$l['argprint'] = join(', ', $argprint);
			$l['class'] = isset($l['class']) ? $l['class'] : '';
			$l['type'] = isset($l['type']) ? $l['type'] : '';
			$l['function'] = isset($l['function']) ? $l['function'] : '';
			$l['file'] = isset($l['file']) ? $l['file'] : 'Unknown File';
			$l['line'] = isset($l['line']) ? $l['line'] : 'Unknown Line';
		}
		
		return $backtrace;
	}
	
	private static function writeLog($content) {
		$path = basePath("/var/errors/") . date('Y/m/d/');
		mkdir_recursive($path);
		
		$hash = sha1($content);
		
		if (file_exists($path . $hash)) {
			return;
		}
		
		file_put_contents($path . $hash, $content);
	}
	
	private static function errorBody($backtrace, $body='') {
		$errorText = $body ? "\n$body\n" : '';
		$errorText .= "<div>";

		$errorText .= "\nBACKTRACE\n";
		$errorText .= "=========\n";
		foreach($backtrace as $i=>$l) {
			$errorText .= "[$i] {$l['class']}{$l['type']}{$l['function']}({$l['argprint']})\n";
			$errorText .= "[{$l['file']}:{$l['line']}]";
			$errorText .= "\n\n";
		}

		$errorText .= "</div>\n";
		return $errorText;
	}
	
	public static function errorHandler($errno, $errstr, $errfile, $errline, $body='') {
		$errno = $errno & error_reporting();
		if($errno == 0) return;

		// Limit the number of errors shown on a single page.
		static $error_count = 0;
		$error_count++;
		$full_error = true;

		if ($error_count > (isset($GLOBALS['MAX_ERROR_TRACES']) ? $GLOBALS['MAX_ERROR_TRACES'] : 5)) {
			$full_error = false;
		}

		if(!defined('E_STRICT'))            define('E_STRICT', 2048);
		if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

		// Capture all output. We may not want to print it to the screen.
		$errorText = '';

		$errorText .= "<b>";
		switch($errno){
			case E_ERROR:               $errorText .= "Error";                  break;
			case E_WARNING:             $errorText .= "Warning";                break;
			case E_PARSE:               $errorText .= "Parse Error";            break;
			case E_NOTICE:              $errorText .= "Notice";                 break;
			case E_CORE_ERROR:          $errorText .= "Core Error";             break;
			case E_CORE_WARNING:        $errorText .= "Core Warning";           break;
			case E_COMPILE_ERROR:       $errorText .= "Compile Error";          break;
			case E_COMPILE_WARNING:     $errorText .= "Compile Warning";        break;
			case E_USER_ERROR:          $errorText .= "User Error";             break;
			case E_USER_WARNING:        $errorText .= "User Warning";           break;
			case E_USER_NOTICE:         $errorText .= "User Notice";            break;
			case E_STRICT:              $errorText .= "Strict Notice";          break;
			case E_RECOVERABLE_ERROR:   $errorText .= "Recoverable Error";      break;
			default:                    $errorText .= "Unknown error ($errno)"; break;
		}
		$errorText .= ":</b> <i>$errstr</i> in <b>$errfile</b> on line <b>$errline</b>\n\n";
		
		$backtrace = self::prepareBacktrace(function_exists('debug_backtrace') ? debug_backtrace() : array(), true);
		
		// Display the first error with a nice error page.
		if (ini_get('display_errors') && PHP_SAPI !== "cli") {
			$view = array();
			
			$view['error'] = $errorText;
			$view['trace'] = $backtrace;
			
			self::renderErrorPage($view);
			exit;
		}
		
		if($full_error && $backtrace){
			$errorText .= self::errorBody($backtrace, $body);
		}

		// Display full error when running from command line
		if (ini_get('display_errors') && PHP_SAPI === "cli") {
			echo strip_tags($errorText);
		}

		// Log errors.
		if (ini_get('log_errors')) {
			self::writeLog(strip_tags($errorText));
		}

		if(self::$fatalErrorMask & $errno) {
			die(self::errorMessage());
		}
	}
	
	private static function errorMessage() {
		return "An error has occured with the website. If possible, please contact {$GLOBALS['EMAIL_SUPPORT']} for support, and to alert us to the issue.";
	}

	public static function exceptionHandler($e) {
		$cls = get_class($e);
		$code = $e->getCode() ? " (" . $e->getCode() . ")" : '';
		$msg = $e->getMessage();
		$line = $e->getLine();
		$file = $e->getFile();
		
		$errorText = "<b>{$cls}{$code}:</b> <i>{$msg}</i> in {$file} on line {$line}\n";
		$backtrace = self::prepareBacktrace($e->getTrace());
		
		// Display the first error with a nice error page.
		if (ini_get('display_errors') && PHP_SAPI !== "cli") {
			$view = array();
			
			$view['error'] = $errorText;
			$view['trace'] = $backtrace;
			
			self::renderErrorPage($view);
			exit;
		}

		$errorText .= self::errorBody($backtrace);

		// Display full error when running from command line
		if (ini_get('display_errors') && PHP_SAPI === "cli") {
			echo strip_tags($errorText);
		}

		// Log errors.
		if (ini_get('log_errors')) {
			self::writeLog(strip_tags($errorText));
		}

		die(self::errorMessage());
	}
	
	public static function setDirectories() {
		$GLOBALS['BASE_PATH'] = dirname(dirname(dirname(__FILE__)));

		set_include_path(
			get_include_path() . PATH_SEPARATOR .
			$GLOBALS['BASE_PATH'] . PATH_SEPARATOR .
			$GLOBALS['BASE_PATH'] . '/lib' . PATH_SEPARATOR
		);
		
		$GLOBALS['PUBLIC_PATH'] = $GLOBALS['BASE_PATH'] . '/public';

		$depth = substr_count(
			str_replace(
				str_replace('\\', '/', strtolower($GLOBALS['BASE_PATH'])),
				'', 
				strtolower(dirname(str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']))))
			),
		'/');

		$GLOBALS['BASE_URI'] = dirname($_SERVER['SCRIPT_NAME']);

		for ($i=0; $i<$depth; $i++) {
			$GLOBALS['BASE_URI'] = dirname($GLOBALS['BASE_URI']);
		}
		// Required to prevent things like href="//path/to/file.php" on
		// a live site.
		if($GLOBALS['BASE_URI'] == '/'
			|| $GLOBALS['BASE_URI'] == '\\'
			|| $GLOBALS['BASE_URI'] == '.'
		) $GLOBALS['BASE_URI'] = '';

		// Next try to calculate a unique project name. This will result in an empty
		// string on live sites.
		$GLOBALS['PROJECT_NAME'] = strtolower(str_replace('/', '', $GLOBALS['BASE_URI']));
	}
}

date_default_timezone_set('Europe/London');
Boot::setDirectories();
require('Common/include.php');

// Define current deployment state.
require('config/deployment.php');
require('config/deployments/' . DEPLOYMENT . '.php');

Boot::startErrorHandling();

// The shared config
require('config/config.php');		

// And finally your personal settings.
if(file_exists($GLOBALS['BASE_PATH'] . '/config/local_settings.php')) {
	include('config/local_settings.php');
}
