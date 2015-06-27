<?php

function layoutClass($props, $last) {
	$cls = array();
	
	if (isset($props->sizeType)) {
		if ($props->sizeType == "grid") {
			$cls[] = "span-" . $props->size;
		} else {
			$cls[] = "span-" . $props->size;
		}
	}

	if ($last) {
		$cls[] = "last";
	}
	
	return join(" ", $cls);
}
