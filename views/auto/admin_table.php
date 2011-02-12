<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo WoolTable::displayName($table) ?></li>
	</ol>
</div>

<div class="pad">
	<h1><?php echo WoolTable::displayName($table) ?></h1>
	<p><?php echo WoolTable::description($table) ?></p>
	
	<?php echo linkTo("New " . WoolTable::shortName($table), array("action"=>"edit", "table"=>$table), 'class="btnLink icon iconAddItem"') ?>
</div>

<div class="pad">
	<div class="pod">
		<div class="padh">
			Filter:
		</div>
	</div>
	
	<div class="columnSelect">
		<div class="button">
			<a href="#" class="icon iconColumn">Columns</a>
		
			<div class="pod podOverlay">
				<div class="head">
					<h2 class="icon iconColumn">Columns</h2>
				</div>
				
				<form method="post" action="<?php echo routeUri(array("controller"=>"api", "action"=>"columnSelect")) ?>" class="pad">
					<input type="hidden" name="direct" value="<?php echo Request::uriForDirect() ?>" />
					<input type="hidden" name="table" value="<?php echo $table ?>" />
					
					<table>
						<?php foreach ($allColumns as $column) { ?>
						<tr>
							<td><input type="checkbox" name="cols[<?php echo $column ?>]" <?php echo in_array($column, $columns) ? 'checked="checked"' : '' ?> /></td>
							<td><?php echo WoolTable::columnName($table, $column) ?></td>
						</tr>
						<?php } ?>
					</table>
					
					<input type="submit" value="Update" class="btnLink icon iconReply" />
				</form>
			</div>
		</div>
		
		<div class="button">
			<?php echo linkTo("Reset All", array("action"=>"reset", "table"=>$table), 'class="icon iconReset"') ?>
		</div>
		
		<div class="clear"></div>
	</div>
	
	<div class="pod">
		<table class="dataGrid" data-gridTable="<?php echo $table ?>" data-headerUpdate="<?php echo routeUri(array("controller"=>"api", "action"=>"headerUpdate")) ?>" data-dragRows="<?php echo routeUri(array("controller"=>"api", "action"=>"rowOrder")) ?>">
			<thead>
				<tr>
					<th></th>
					<?php foreach ($columns as $column=>$sort) { ?>
					<th class="dragable <?php echo gridHeaderClass($table, $column, $sort) ?>" data-column="<?php echo $column ?>"><span><?php echo WoolTable::columnName($table, $column) ?></span></th>
					<?php } ?>
					<th width="1"></th>
				</tr>
			</thead>
			
			<tbody>
				<?php if (!count($data)) { ?>
				<tr>
					<td colspan="999">No items found.</td>
				</tr>
				<?php } ?>
				<?php $self->renderPartials("row", $data, "item", array("table"=>$table, "columns"=>$columns)) ?>
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
						<?php echo linkTo("New " . WoolTable::shortName($table), array("action"=>"edit", "table"=>$table), 'class="icon iconAddItem"') ?>
						</div>
						
						<?php
						if ($self->canRenderPartial($table . "_row_form")) {
							$self->renderPartial($table . "_row_form");
						}
						?>
					</td>
				</tr>
			</tfoot>
		</table>
		
		<div class="foot">
			<div class="pod podNested pad">
				<?php $self->renderPartial('/grid/nav', array("pager"=>$data)) ?>
			</div>
		</div>
	</div>
</div>

<?php $self->renderPartial('column_edit') ?>
