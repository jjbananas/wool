<?php 

function rowClear($num, $perRow) {
	return (($num+1) % $perRow == 0) ? '<div class="clear"></div>' : '';
}
