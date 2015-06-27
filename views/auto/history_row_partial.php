<tr>
	<?php foreach ($columns as $column) { ?>
	<?php if ($ref = Schema::columnIsKey($table, $column)) { ?>
	<td><?php echo keyColumnDisplay($item, $column, $ref) ?></td>
	<?php } else { ?>
	<td><?php echo $item->$column ?></td>
	<?php } ?>
	<?php } ?>
	<td><?php echo $item_num > 0 ? linkTo("Revert", array("action"=>"revert", "table"=>$table, "id"=>$item->$tableUnique, "revertId"=>$item->$unique), 'class="btnLink icon iconEdit"') : '' ?></td>
</tr>
