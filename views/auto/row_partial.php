<tr data-unique="<?php $u = Schema::uniqueColumn($table); echo $item->$u ?>" <?php echo isset($selected) ? 'class="selected"' : '' ?>>
	<td class="rowSelect"><input type="checkbox" name="item[<?php echo $item->$u ?>]" <?php echo checked(isset($selected)) ?> /></td>
	<td class="rowJoined"><img src="<?php echo publicUri("/images/icons/table_relationship.png") ?>" alt="Joined" /></td>
	<?php foreach ($grid->visibleColumns() as $column=>$sort) { ?>
	<?php if ($self->canRenderPartial("/{$table}/auto_cell_{$column}")) { ?>
	<td><?php $self->renderPartial("/{$table}/auto_cell_{$column}", array("table"=>$table, "grid"=>$grid, "item"=>$item, "sort"=>$sort)) ?></td>
	<?php } else if ($ref = Schema::columnIsKey($table, $column)) { ?>
	<td><?php echo keyColumnDisplay($item, $column, $ref) ?></td>
	<?php } else { ?>
	<td><?php echo $item->$column ?></td>
	<?php } ?>
	<?php } ?>
	<td><?php echo linkTo("Edit", array("action"=>"edit", "table"=>$table, "id"=>$item->$u), 'class="btnLink icon iconEdit"') ?></td>
</tr>
