<?php

class WoolEvent {
	private static $handlers = array();

	// Scan cron directory and add any functions that have schedulers.
	public static function scanEventFiles($forceUpdate=false) {
		if (!$forceUpdate && self::$handlers) {
			return;
		}

		foreach (glob(privatePath("/events/*")) as $eventFile) {
			require_once($eventFile);
			$name = basename($eventFile, ".php") . "Event";

			foreach (get_class_methods($name) as $method) {
				if (method_exists(__CLASS__, $method)) {
					continue;
				}

				if (substr($method, 0, 2) != "on") {
					continue;
				}

				self::$handlers[$method][] = $name;
			}
		}
	}

	public static function trigger($event, $data=array()) {
		self::scanEventFiles();

		if (!isset(self::$handlers[$event])) {
			return;
		}

		foreach (self::$handlers[$event] as $handler) {
			call_user_func(array($handler, $event), $data);
		}
	}
}
