<?php

class TestCron extends WoolCron {
	public static function runTests() {
		return true;
	}

	public static function runTestsSchedule() {
		return time() + 120;
	}
}
