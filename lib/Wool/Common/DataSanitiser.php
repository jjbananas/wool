<?php
/*
 * Data sanitiser contains a set of methods for sanitising data entered on the front end for large amounts of public content (forum posts, messages etc...). 
 * This will initially be simple but may be upgraded to include greater sanitisation such as leet speek broken words etc. 
 *
 * TODO: This currently does not have any SQL sanitisation as thats currently dealt with in the form
 * 
 */
define('URL_REPLACED_MESSAGE','');
class DataSanitiser{
	private $removeUrls = true;
	private $removeScript = true;
	
	//List of phrases that may be contained within the text for  
	 var $urlProtocols = array(
		'http',
	 	'https',
		'sftp',
		'ftp'	
	);
	
	var $urlPrefixes = array(
		'www',
	);
	
	var $urlSuffixes = array(
		'com',
		'co.uk',
		'biz',
		'info'
	);
	
	function __construct($options=array()) {
		if (isset($options['removeUrls'])) { $this->removeUrls = $options['removeUrls']; }
		if (isset($options['removeScript'])) { $this->removeScript = $options['removeScript']; }
	}


	function SanitiseData($unsanitisedString){
		//Step 1: Remove urls
		if ($this->removeUrls) {
			$unsanitisedString = $this->RemoveURL($unsanitisedString);
		}
		//Step 2: convert HTML into HTML entities
		if ($this->removeScript) {
			$unsanitisedString = $this->RemoveScript($unsanitisedString);
		}
		return $unsanitisedString;
	}
	
	
	/**
	 * Essentially runs three large Search and replace Regular expresssions search and replace on the page
	 * 
	 * TODO: Could no doubt be converted down into one large regular expression
	 *
	 * @param string $unsanitisedString
	 * 
	 * @return String Sanitisedstring - returns the sanitised string
	 */
	function RemoveURL($unsanitisedString){
		//Search by common protocols
		$currentRegEx = '/(';
		$protocolCount = 0;
		foreach($this->urlProtocols as $protocol){
			$currentRegEx .= $protocol;
			$protocolCount++;
			if($protocolCount < count($this->urlProtocols)){
				$currentRegEx .= '|';
			}
		}
		$currentRegEx .= ')[^\s]+'; //Ensure that it doesnt exclude the words on their own
		$currentRegEx .= '/i';
		$unsanitisedString = preg_replace($currentRegEx,URL_REPLACED_MESSAGE,$unsanitisedString);
		//Exclude prefixes
		$currentRegEx = '/(';
		$prefixCount = 0;
		foreach($this->urlPrefixes as $prefix){
			$currentRegEx .= $prefix;
			$prefixCount++;
			if($prefixCount< count($this->urlPrefixes)){
				$currentRegEx .= '|';
			}
		}
		$currentRegEx .= ')[.][^\s]+'; //Ensure that it doesnt exclude the words on their own
		$currentRegEx .= '/i';
		$unsanitisedString = preg_replace($currentRegEx,URL_REPLACED_MESSAGE,$unsanitisedString);
		//Exclude suffixes
		$currentRegEx = '/[^\s]+[.](';
		$suffixCount = 0;
		foreach($this->urlSuffixes as $suffix){
			$currentRegEx .= $suffix;
			$suffixCount++;
			if($suffixCount < count($this->urlSuffixes)){
				$currentRegEx .= '|';
			}
		}
		$currentRegEx .= ')[^\s]*'; //Ensure that it doesnt exclude the words on their own
		$currentRegEx .= '/i';
		$unsanitisedString = preg_replace($currentRegEx,URL_REPLACED_MESSAGE,$unsanitisedString);
		return $unsanitisedString;
	}

	/**
	 * Deal with script, currentluy just converts to html entities
	 * 
	 * @param mixed $unsanitisedString
	 * 
	 * @return string SanitisedString - the sanitised strign
	 */
	function RemoveScript($unsanitisedString){
		$sanitisedString = htmlentities($unsanitisedString,null,'UTF-8');
		$sanitisedString = strip_tags($sanitisedString);
		return $sanitisedString; 
	}
};	

?>