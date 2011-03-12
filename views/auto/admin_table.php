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
	<div class="pod filter podToggleOpen <?php echo $data->isFiltering() ? 'active' : '' ?>">
		<div class="filterControls">
			<a href="<?php echo Request::uri(array("{$table}_clear"=>"true")) ?>" class="icon iconReset">Clear Search</a>
			<form>
				<input type="submit" class="noBtn icon iconReset" value="Load" />
				<select>
					<option>Option #1</option>
				</select>
			</form>
			
			<div class="clear"></div>
		</div>
		
		<form class="filterOptions">
			Search: <?php echo text_field_tag("{$table}_filter", $data->filterParam(), array('class'=>"mainFilter")) ?>
			
			<div class="additionalOptions">
				<?php foreach (Schema::filterableColumns($table) as $colName=>$filterColumn) { ?>
				<div class="s1of3">
					<?php
						$self->renderPartial(
							(SqlTypes::isDate($filterColumn['type'])) ? "filter_date" : "filter_options",
							array("table"=>$table, "column"=>$colName, "data"=>$data)
						);
					?>
				</div>
				<?php } ?>
				
				<div class="save">
					<label>Save As:</label> <input type="text" />
					<input type="submit" class="btnLink btnLinkLight btnLinkThin icon iconSearch" value="Search" />
				</div>
			</div>
		</form>
		
		<div class="foot">
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
							<td><input type="checkbox" name="cols[<?php echo $column ?>]" <?php echo isset($columns[$column]) ? 'checked="checked"' : '' ?> /></td>
							<td><?php echo Schema::columnName($table, $column) ?></td>
						</tr>
						<?php } ?>
					</table>
					
					<input type="submit" value="Update" class="btnLink btnLinkLight icon iconReply" />
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
					<th class="dragable <?php echo gridHeaderClass($table, $column, $sort) ?>" data-column="<?php echo $column ?>"><span><?php echo Schema::columnName($table, $column) ?></span></th>
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
						<?php echo linkTo("New " . Schema::shortName($table), array("action"=>"edit", "table"=>$table), 'class="icon iconAddItem"') ?>
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
