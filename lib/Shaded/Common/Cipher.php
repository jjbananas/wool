<?php

define('DEFAULT_ENCRYPTION_KEY', 'Ug975g6fGjhVHf7oiG67tkl4yWPit5FtvbTF6JKh5iyFfHyUJygTvhEZ');
define('DEFAULT_INITALIZATION_VECTOR', 'Ug975g6fGjhVHf7oiG67tkl4yWPit5FtvbTF6JKh5iyFfHyUJygTvhEZ');

class Cipher {
	private static $key = 'sdfjsklfds';
	
	public static function blowfishEnc($plain) {
		return self::encrypt($plain, MCRYPT_BLOWFISH);
	}
	public static function blowfishDec($cyphertext) {
		return self::decrypt($cyphertext, MCRYPT_BLOWFISH);
	}
	
	private static function encrypt($value, $algorithm) {
		$resource = mcrypt_module_open($algorithm, "", "ofb", "");

		$ivSize = mcrypt_enc_get_iv_size($resource);
		$random = (stristr(strtolower(php_uname('s')), 'windows')) ? MCRYPT_RAND : MCRYPT_DEV_RANDOM;
		//$iv = mcrypt_create_iv($ivSize, $random);
		
		$iv = substr(DEFAULT_INITALIZATION_VECTOR, 0, $ivSize);
		
		mcrypt_generic_init($resource, DEFAULT_ENCRYPTION_KEY, $iv);
		$data = mcrypt_generic($resource, $value);
		mcrypt_generic_deinit($resource);

		$data = base64_encode($iv.$data);

		mcrypt_module_close($resource);

		return $data;
	}

	private static function decrypt($data) {
		$resource = mcrypt_module_open($algorithm, "", null, "");

		$data = base64_decode($data);

		$ivSize = mcrypt_enc_get_iv_size($resource);
		$iv = substr($data, 0, $ivSize);
		$data = substr($data, $ivSize);

		mcrypt_generic_init($resource, DEFAULT_ENCRYPTION_KEY, $iv);
		$value = mdecrypt_generic($resource, $data);
		mcrypt_generic_deinit($resource);

		mcrypt_module_close($resource);

		return $value;
	}
}
