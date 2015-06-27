<?php
if ($self->canRenderPartial("/{$table}/auto_grid")) {
	$self->renderPartial("/{$table}/auto_grid", array("grid"=>$grid));
} else {
?>
<div class="pod">
	<form method="post" action="<?php echo routeUri(array("action"=>"gridAction", "table"=>$table)) ?>">
		<table class="dataGrid" data-gridTable="<?php echo $table ?>" data-headerUpdate="<?php echo routeUri(array("controller"=>"api", "action"=>"headerUpdate")) ?>" <?php echo Schema::tableSequenced($table) ? 'data-dragRows="' . routeUri(array("controller"=>"api", "action"=>"rowOrder")) . '"' : '' ?>>
			<thead>
				<tr>
					<th class="rowSelect"><input type="checkbox" class="checkAll" /></th>
					<?php if ($grid->showJoins()) { ?>
					<th class="rowJoined"><img src="<?php echo publicUri("/images/icons/table_relationship.png") ?>" alt="Joined" /></th>
					<?php } ?>
					<?php foreach ($grid->visibleColumns() as $column=>$sort) { ?>
					<th class="dragable <?php echo gridHeaderClass($table, $column, $grid->sortColumns()) ?>" data-column="<?php echo $column ?>"><span><?php echo Schema::columnName($table, $column) ?></span></th>
					<?php } ?>
					<th width="1"></th>
				</tr>
			</thead>
			
			<tbody>
				<?php if (!count($grid)) { ?>
				<tr>
					<td colspan="999">No items found.</td>
				</tr>
				<?php } ?>
				<?php
				if ($self->canRenderPartial("/{$table}/auto_row")) {
					$self->renderPartials("/{$table}/auto_row", $grid, "item", array("table"=>$table, "grid"=>$grid));
				} else {
					$self->renderPartials("row", $grid, "item", array("table"=>$table, "grid"=>$grid));
				}
				?>
			</tbody>
			
			<tfoot>
				<tr>
					<td colspan="999">
						<div class="newRow">
						<?php echo linkTo("New " . Schema::shortName($table), array("action"=>"edit", "table"=>$table), 'class="icon iconAddItem"') ?>
						</div>
						
					</td>
				</tr>
				
				<?php if (count($grid)) { ?>
				<tr>
					<td colspan="999">
						<div class="gridButtons">
							<?php if (isset($buttonPartial)) { ?>
							<?php $self->renderPartial($buttonPartial, array()) ?>
							<?php } else { ?>
							<input type="submit" class="btnLink btnLinkLight btnLinkThin icon iconDelete" name="delete" value="Delete" />
							<?php } ?>
						</div>
					</td>
				</tr>
				<?php } ?>
			</tfoot>
		</table>
	</form>
	
	<?php if ($grid->totalPages() > 0) { ?>
	<div class="foot">
		<div class="pod podNested pad">
			<?php $self->renderPartial('/grid/nav', array("pager"=>$grid)) ?>
		</div>
	</div>
	<?php } ?>
</div>
<?php
}
?>
