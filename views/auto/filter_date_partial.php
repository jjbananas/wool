<label><?php echo Schema::columnName($table, $column) ?></label>
<div class="date">
	<span class="label">Start</span>
	<input type="date" />
	
	<span class="label">End</span>
	<?php echo date_field_tag("{$table}_filter", $data->filterParam()) ?>
</div>
