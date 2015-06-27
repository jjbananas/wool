<?php

require_once("Wool/App/Message/Message.php");

class MessageCron extends WoolCron {
	// Process previously queued messages.
	public static function processMessages($lastRun) {
		return WoolMessage::processQueue();
	}

	public static function processMessagesSchedule($lastRun, $success) {
		// One minute from now.
		return mktime_relative(0,1,0);
	}


	// Automatically add new message types dropping into the directory.
	public static function scanNewMessageTypes($lastRun) {
		WoolMessage::scanMessageFiles();
		return true;
	}

	public static function scanNewMessageTypesSchedule($lastRun, $success) {
		// Each day should do. Most people will probably want to manually refresh anyway.
		return mktime_relative(0,0,0, 0,1,0);
	}
}
