<?php

function layoutClass($props) {
	$cls = array();
	
	if (isset($props->grid)) {
		$cls[] = "span-" . $props->grid;
	}
	
	return join(" ", $cls);
}
