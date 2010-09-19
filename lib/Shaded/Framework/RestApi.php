<?php
// Classes for handling rest sessions through curl.

class RestTransaction
{
	public $responseUrl;
	public $responseCode;
	public $responseHeaders;
	public $response;	// Will include redir headers if FOLLOWLOCATION == true.
	public $requestHeader;
	public $errors = array();
}
class RestSession
{
	private $curl = null;
	public $transactions = array();
	private $transaction;	// Current transaction, placed in $transactions when complete.
	private $testCasesSuccess = array();
	private $testCasesFail = array();

	// Choose a cookie file to store your session in.
	// REST sessions should not share cookie files.
	function __construct($cookie_file="restcookies.txt", $opts=array())
	{
		if (!is_string($cookie_file)) die('$cookie_file must be a string.');
		$cookie_file = $GLOBALS['TEMP_DIR_WS'] . $cookie_file;
		$this->curl = curl_init();
		$this->curlOpts = array(
			CURLINFO_HEADER_OUT => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_COOKIEFILE => $cookie_file,
			CURLOPT_COOKIEJAR => $cookie_file
			);
		$this->curlOpts = array_merge_strict($this->curlOpts, $opts);
	}

	function __destruct()
	{
		curl_close($this->curl);
	}

	function lastTransaction()
	{
		return last_element($this->transactions);
	}

	function clearTests()
	{
		$this->testCasesSuccess = array();
		$this->testCasesFail = array();
	}

	private function _setupRequest($url, $opts=array())
	{
		if ($this->curl) curl_close($this->curl);
		$this->curl = curl_init();
		$this->curlOpts = array_merge_strict($this->curlOpts, $opts);
		curl_setopt_array($this->curl, $this->curlOpts);
		curl_setopt($this->curl, CURLOPT_URL, $url);
	}

	function addHeaderTestCase($type, $case, $property, $datum)
	{
		// XXX SIMPLEST IMPLEMENTATION.  Test case stuff needs better abstraction.
		$type = ucfirst($type);
		$datum = addslashes($datum);
		$testCases = "testCases{$type}";
		switch ($case) {
		case 'begins':
			array_push($this->$testCases, "strpos(\$this->responseHeaders['$property'], '$datum') === 0;");
		}
	}
	function addTestCase($type, $case, $property, $datum)
	{
		$allowed_types = array('success', 'fail');
		if (!in_array($type, $allowed_types))
			die ('arg $type must be one of: ' . implode(', ', $allowed_types));
		$type = ucfirst($type);
		$datum = addslashes($datum);
		$testCases = "testCases{$type}";
		if (is_property_visibility(get_class($this), $property, 'public')) {
			switch ($case) {
			case 'begins':
				array_push($this->$testCases, "strpos(\$this->$property, '$datum') === 0;");
				break;
			case 'contains':
				array_push($this->$testCases, "strpos(\$this->$property, '$datum') !== false;");
				break;
			case 'identical':
				array_push($this->$testCases, "\$this->$property  === '$datum';");
			}
		}
	}

	function evalTestCases()
	{
		foreach ($this->testCasesSuccess as $t) {
			if (!eval($t)) {
				$this->transaction->errors[] = "Success test case failed: $t";
				return false;
			}
		}
		foreach ($this->testCasesFail as $t) {
			if (eval($t)) {
				$this->transaction->errors[] = "Failure test case asserted: $t";
				return false;
			}
		}
		return true;
	}

	function get($url, $opts=array())
	{
		$this->transaction = new RestTransaction;
		$this->_setupRequest($url, $opts);
		$response = curl_exec($this->curl);
		$this->_populateResponse($response);
		//TODO: return $this->evalTestCases();  // See $this->post() for ex.
		$this->transactions[] = $this->transaction;
		return $this->transaction->response;
	}

	function post($url, $postdata, $opts=array())
	{
		$this->transaction = new RestTransaction;
		$this->_setupRequest($url, $opts);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, toQueryString($postdata));
		$response = curl_exec($this->curl);
		$this->_populateResponse($response);
		$status = true;
		if (!empty($this->testCases)) {
			if (!$this->evalTestCases()) $status = false;
		}
		$this->transactions[] = $this->transaction;
		return $status? $this->transaction->response : false;
	}

	private function _populateResponse($response)
	{
		$this->transaction->responseUrl
			= curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);
		$this->transaction->responseCode
			= curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		$this->transaction->requestHeader
			= curl_getinfo($this->curl, CURLINFO_HEADER_OUT);
		if ($response == false) $this->transaction->errors[] = curl_error($this->curl);
		if (!$response) return;
		$r = preg_split('/\n\s*\n|\r\n\s*\r\n/', $response, 2);
		$headers = $r[0];
		$this->transaction->response = isset($r[1])? $r[1]: $r[0];
		$headers = preg_split('/\n|\r\n/', $headers);
		foreach ($headers as $h) {
			$x = explode(':', $h);
			if (count($x) == 2) {
				$this->transaction->responseHeaders[$x[0]] = trim($x[1]);
			}
		}
	}
}
