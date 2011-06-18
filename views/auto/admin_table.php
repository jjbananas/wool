<?php
	$self->js("/js/jquery.positionMatch.js");
?>
<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo Schema::displayName($table) ?></li>
	</ol>
</div>

<div class="pad">
	<h1><?php echo Schema::displayName($table) ?></h1>
	<p><?php echo Schema::description($table) ?></p>
	
	<?php echo linkTo("New " . Schema::shortName($table), array("action"=>"edit", "table"=>$table), 'class="btnLink icon iconAddItem"') ?>
	
</div>

<div class="pad">
	<?php $self->renderPartial("grid_filter", array("table"=>$table, "data"=>$grid)) ?>
	
	<?php $self->renderPartial("column_tools", array("table"=>$table, "grid"=>$grid)) ?>
	
	<?php
	if ($self->canRenderPartial("/{$table}/auto_grid")) {
		$self->renderPartial("/{$table}/auto_grid", array("grid"=>$grid));
	} else {
	?>
	<div class="pod">
		<form method="post" action="<?php echo routeUri(array("action"=>"gridAction", "table"=>$table)) ?>">
			<table class="dataGrid" data-gridTable="<?php echo $table ?>" data-headerUpdate="<?php echo routeUri(array("controller"=>"api", "action"=>"headerUpdate")) ?>" data-dragRows="<?php echo routeUri(array("controller"=>"api", "action"=>"rowOrder")) ?>">
				<thead>
					<tr>
						<th class="rowSelect"><input type="checkbox" class="checkAll" /></th>
						<th class="rowJoined"><img src="<?php echo publicUri("/images/icons/table_relationship.png") ?>" alt="Joined" /></th>
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
					<?php /*
					<tr class="totals">
						<td>Totals:</td>
						<?php foreach ($columns as $column) { ?>
						<td><?php echo arraySumInner($data, $column) ?></td>
						<?php } ?>
						<td></td>
					</tr>
					<tr class="totals">
						<td>Average:</td>
						<?php foreach ($columns as $column) { ?>
						<td><?php echo arrayAvgInner($data, $column) ?></td>
						<?php } ?>
						<td></td>
					</tr>
					*/ ?>
					
					
					<tr>
						<td colspan="999">
							<div class="newRow">
							<?php echo linkTo("New " . Schema::shortName($table), array("action"=>"edit", "table"=>$table), 'class="icon iconAddItem"') ?>
							</div>
							
						</td>
					</tr>
					
					<tr>
						<td colspan="999">
							<div class="gridButtons">
								<input type="submit" class="btnLink btnLinkLight btnLinkThin icon iconDelete" name="delete" value="Delete" />
							</div>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>
		
		<div class="foot">
			<div class="pod podNested pad">
				<?php $self->renderPartial('/grid/nav', array("pager"=>$grid)) ?>
			</div>
		</div>
	</div>
	<?php
	}
	?>
</div>

<?php
if ($self->canRenderPartial($table . "_row_form")) {
	$self->renderPartial($table . "_row_form");
}
?>
<?php $self->renderPartial('column_edit') ?>
