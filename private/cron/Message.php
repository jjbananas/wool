<?php

class MessageCron extends WoolCron {
	public static function processMessages($lastRun) {
		return true;
	}

	public static function processMessagesSchedule($lastRun, $success) {
		return mktime_relative(0,1,0);
	}
}
