<tr data-unique="<?php $u = WoolTable::uniqueColumn($table); echo $item->$u ?>">
	<td class="rowSelect"><input type="checkbox" name="item[<?php echo $item->$u ?>]" <?php echo checked(isset($selected)) ?> /></td>
	<?php foreach ($columns as $column=>$sort) { ?>
	<td><?php echo $item->$column ?></td>
	<?php } ?>
	<td><?php echo linkTo("Edit", array("action"=>"edit", "table"=>$table, "id"=>$item->$u), 'class="btnLink icon iconEdit"') ?></td>
</tr>
