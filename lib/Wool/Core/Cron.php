<?php

class WoolCron {
	// Scan cron directory and add any functions that have schedulers.
	public static function scanCronFiles() {
		$existingCrons = WoolTable::fetchAll("cron");

		foreach (glob(privatePath("/cron/*")) as $cronFile) {
			require_once($cronFile);
			$name = basename($cronFile, ".php") . "Cron";

			foreach (get_class_methods($name) as $method) {
				if (method_exists(__CLASS__, $method)) {
					continue;
				}

				if (substr($method, -8) != "Schedule") {
					continue;
				}

				$cronFunction = substr($method, 0, -8);

				if (!method_exists($name, $cronFunction)) {
					trigger_error("Cron attempting to schedule function that doesn't exist: '{$cronFunction}'", E_USER_WARNING);
				}


				$existingFuncs = $existingCrons->byGroup("cronClass", $name);

				if ($existingFuncs && $existingFuncs->by("cronFunction", $cronFunction)) {
					continue;
				}

				$cron = WoolTable::blank("cron");
				$cron->cronClass = $name;
				$cron->cronFunction = $cronFunction;
				$cron->scheduledOn = sqlDate(call_user_func(array($name, $method), null, true));

				if (!WoolTable::save($cron)) {
					trigger_error("Error adding cron: '{$cronFunction}'", E_USER_ERROR);
				}
			}
		}
	}

	public static function runAllCrons() {
		$crons = new RowSet(<<<SQL
select *
from cron
where
	scheduledOn <= now()
	and (lastRunOn is null or lastRunOn < scheduledOn)
SQL
		);

		foreach ($crons as $cron) {
			self::runCron($cron);
		}

		if (!WoolTable::save($crons)) {
			trigger_error("Error updating crons!", E_USER_ERROR);
		}
	}

	private static function runCron($cron) {
		$file = substr($cron->cronClass, 0, -4) . ".php";
		require_once(privatePath("/cron/" . $file));

		$lastRun = strtotime($cron->lastRunOn);

		$success = !!call_user_func(array($cron->cronClass, $cron->cronFunction), $lastRun);

		if (method_exists($cron->cronClass, $cron->cronFunction . "Schedule")) {
			$cron->scheduledOn = sqlDate(call_user_func(array($cron->cronClass, $cron->cronFunction . "Schedule"), $lastRun, $success));
		}

		$cron->lastRunOn = now();

		// Store entry in the log too.
		$log = WoolTable::blank("cron_log");
		$log->cronId = $cron->cronId;
		$log->runOn = $cron->lastRunOn;
		$log->success = $success;
		$log->message = "";

		WoolTable::save($log);
	}
}
