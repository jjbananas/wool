<?php

// Cron to check for new crons dropped into the cron directory and
// automatically schedule them in.
class CronCron extends WoolCron {
	public static function checkCrons() {
		WoolCron::scanCronFiles();
		return true;
	}

	public static function checkCronsSchedule() {
		// 1 day from now at 01:00.
		return mkdate_relative(0,1,0, 1,0,0);
	}
}
