<?php
	/*
		Localhost configuration.
	*/

	define('DEVELOPER', true);

	// Define database connection variables
	$GLOBALS['DB_HOST'] = 'mr-clever';
	$GLOBALS['DB_NAME'] = 'ignition_master';
	$GLOBALS['DB_USERNAME'] = 'ignition';
	$GLOBALS['DB_PASSWORD'] = 'azex1s';
	
	// Company config.
	$GLOBALS['COMPANY']['site'] = 'localhost';
	$GLOBALS['COMPANY']['email'] = '@azexis.com';
	
	// Email Defaults
	$GLOBALS['EMAIL_FROM'] = 'no-reply' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_ERRORS_FROM'] = 'error-reporter' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_SUPPORT'] = 'support' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_RETURN'] = 'support' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_DAEMON'] = 'mailer-daemon' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_WAREHOUSE'] = 'support' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_BROCHURE_REQUEST'] = 'support' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_EVENT_BOOKING'] = 'support' . $GLOBALS['COMPANY']['email'];
	$GLOBALS['EMAIL_MEDIA_REQUEST'] = 'support' . $GLOBALS['COMPANY']['email'];

	$GLOBALS['USE_SSL'] = false;
?>
