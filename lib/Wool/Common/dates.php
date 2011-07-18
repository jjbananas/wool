<?php

function monthOptions() {
	return array(
		1 => "January", 2 => "February", 3 => "March", 4 => "April",
		5 => "May", 6 => "June", 7 => "July", 8 => "August", 9 => "September",
		10 => "October", 11 => "November", 12 => "December"
	);
}

// Years in select option format. $end can be an end year or a number of years
// after the start year to include.
function yearOptions($start, $end) {
	$years = array();
	
	// Assume values less than $start are a number of years.
	if ($end < $start) {
		$end = $start+$end;
	}
	
	while ($end >= $start) {
		$years[$start] = $start;
		$start++;
	}
	return $years;
}

// Format date string relative to current date. eg. "Tomorrow"
function fromNow($then){
	if(stristr($then, ' ')){
		$then = explode(' ', $then);
		$then = $then[0];
	}
	$now = strtotime(now());
	$then = strtotime($then);
	$diff = $now-$then; // the difference in seconds
	$diff = $diff/60; // the difference in minutes
	$diff = $diff/60; // the difference in hours
	$diff = $diff/24; // the difference in days
	
	if(date('Y-m-d', $now) == date('Y-m-d', $then)){
		return 'Today';
	} else if(strtotime('yesterday') == strtotime(date('Y-m-d', $then))){
		return 'Yesterday';
	} else if($diff<7){
		return date('l', $then);
	} else if(date('Y', $now) == date('Y', $then)) {
		return date('jS M', $then);
	} else {
		return date('d/m/Y', $then);
	}
	
	return $diff;
}

// Checks whether the time given is between the opening and closing times.
// $opening and $closing should be a time sting in 24h format (eg. "17:45")
function timeWithinHours($opening, $closing, $time=null) {
	if (!$time) { $time = time(); }
	
	$opening = explode(":", $opening);
	$closing = explode(":", $closing);
	
	$time = localtime($time, true);
	if (
		$time['tm_hour'] < $opening[0]
		|| ($time['tm_hour'] == $opening[0] && $time['tm_min'] < $opening[1])
		|| $time['tm_hour'] > $closing[0]
		|| ($time['tm_hour'] == $closing[0] && $time['tm_min'] > $closing[1])
	)
	{
		return false;
	}
	
	return true;
}

function weeksBetween($start, $end) {
	$start = strtotime($start);
	$end = strtotime($end);
	
	$weekSecs = 60*60*24*7;
	
	return floor(($end-$start) / $weekSecs);
}

function mkdate($m,$d,$y) {
	return mktime(0,0,0, $m,$d,$y);
}

function mkdate_relative($m=0,$d=0,$y=0, $h=0,$min=0,$s=0) {
	return mktime($h, $min, $s, date("n")+$m,date("j")+$d,date("Y")+$y);
}

function mktime_relative($h=0,$m=0,$s=0, $mon=0,$d=0,$y=0) {
	return mktime(
		date("H") + $h,
		date("i") + $m,
		date("s") + $s,
		date("n") + $mon,
		date("j") + $d,
		date("Y") + $y
	);
}
