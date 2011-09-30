<?php
/*
	Configuration shared between all deployments.
	
	Anything in this file will overwrite the deployment config. So if you move
	something from here to the deployment specific config, make sure to copy it
	to all configs and remove it from here.
*/

// Logging emails are sent to the following address from the live site.
$GLOBALS['EMAIL_DEVELOPER_LOG'] = 'tony@tonymarklove.net';
