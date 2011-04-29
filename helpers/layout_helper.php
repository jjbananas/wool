<?php

function layoutClass($props) {
	$cls = array();
	
	if (isset($props->sizeType)) {
		if ($props->sizeType == "grid") {
			$cls[] = "span-" . $props->size;
		} else {
			$cls[] = "span-" . $props->size;
		}
	}
	
	return join(" ", $cls);
}
