<?php
	$self->js('/components/fancybox/jquery.fancybox-1.3.4.js');
	$self->css('/components/fancybox/jquery.fancybox-1.3.4.css');
	
	$self->js("/js/jquery.positionMatch.js");
?>
<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo Schema::displayName($foreign) ?></li>
	</ol>
</div>

<div class="pad">
	<h1><?php echo Schema::displayName($foreign) ?></h1>
	<p><?php echo Schema::description($foreign) ?></p>
	
	<?php echo linkTo("New " . Schema::shortName($foreign), array("action"=>"edit", "table"=>$foreign), 'class="btnLink icon iconAddItem"') ?>
	
</div>

<div class="pad">
	<div class="pod filter podToggleOpen <?php echo $data->isFiltering() ? 'active' : '' ?>">
		<div class="filterControls">
			<a href="<?php echo Request::uri(array("{$foreign}_clear"=>"true")) ?>" class="icon iconReset">Clear Search</a>
			<form>
				<div class="comboBox">
					<span class="btn btnLink btnLinkThin">Saved Searches</span>
					
					<div class="combo">
						<input type="text" />
						
						<ul class="options">
							<li>Tax free</li>
							<li>Some other</li>
							<li>BT Internet</li>
						</ul>
					</div>
				</div>
			</form>
			
			<div class="clear"></div>
		</div>
		
		<form class="filterOptions">
			Search: <?php echo text_field_tag("{$foreign}_filter", $data->filterParam(), array('class'=>"mainFilter")) ?>
			
			<div class="comboBox">
				<span class="btn btnLink btnLinkThin"><span class="label">Save Search</span></span>
				
				<div class="combo">
					<input type="text" name="<?php echo $foreign . "_save" ?>" data-action="" autocomplete="off" />
					
					<ul class="options">
						<li>Tax free</li>
						<li>Some other</li>
						<li>BT Internet</li>
					</ul>
					
					<ul class="fixed">
						<li><a href="">Manage Saved Searches</a></li>
					</ul>
				</div>
			</div>
			
			<div class="additionalOptions">
				<?php foreach (Schema::filterableColumns($foreign) as $colName=>$filterColumn) { ?>
				<div class="s1of3">
					<?php
						$self->renderPartial(
							(SqlTypes::isDate($filterColumn['type'])) ? "filter_date" : "filter_options",
							array("table"=>$foreign, "column"=>$colName, "data"=>$data)
						);
					?>
				</div>
				<?php } ?>
				
				<div class="save">
					<input type="submit" class="btnLink btnLinkLight btnLinkThin icon iconSearch" value="Search" />
				</div>
			</div>
		</form>
		
		<div class="foot">
		</div>
	</div>
	
	<div class="columnTools">
		<div class="columnSelect button">
			<a href="#" class="icon iconColumn">Columns</a>
		
			<div class="pod podOverlay">
				<div class="head">
					<h2 class="icon iconColumn">Columns</h2>
				</div>
				
				<form method="post" action="<?php echo routeUri(array("controller"=>"api", "action"=>"columnSelect")) ?>" class="pad">
					<input type="hidden" name="direct" value="<?php echo Request::uriForDirect() ?>" />
					<input type="hidden" name="table" value="<?php echo $foreign ?>" />
					
					<table>
						<?php foreach ($allColumns as $column) { ?>
						<tr>
							<td><input type="checkbox" name="cols[<?php echo $column ?>]" <?php echo isset($columns[$column]) ? 'checked="checked"' : '' ?> /></td>
							<td><?php echo Schema::columnName($foreign, $column) ?></td>
						</tr>
						<?php } ?>
					</table>
					
					<input type="submit" value="Update" class="btnLink btnLinkLight icon iconReply" />
				</form>
			</div>
		</div>
		
		<div class="columnSort button">
			<a href="#" class="icon iconSort">Sorting</a>
		
			<div class="pod podOverlay">
				<div class="head">
					<h2 class="icon iconSort">Sorting</h2>
				</div>
				
				<form method="post" action="<?php echo routeUri(array("controller"=>"api", "action"=>"columnSort")) ?>" class="pad">
					<input type="hidden" name="direct" value="<?php echo Request::uriForDirect() ?>" />
					<input type="hidden" name="table" value="<?php echo $foreign ?>" />
					
					<table>
						<tr class="duplicate">
							<td>
								<?php echo select_box_tag("cols[1][sort]", $columnOptions, null, array(), array("disabled"=>"disabled")) ?>
							</td>
							<td>
								<?php echo select_box_tag("cols[{num}][dir]", sqlOrderOptions(), null, array(), array("disabled"=>"disabled")) ?>
							</td>
							<td>
								<a href="#" class="delRow">Del</a>
							</td>
						</tr>
						
						<?php foreach ($sortColumns as $column=>$dir) { ?>
						<tr>
							<td>
								<?php 
								echo select_box_tag("cols[1][sort]", $columnOptions, $column);
								?>
							</td>
							<td>
								<?php echo select_box_tag("cols[1][dir]", sqlOrderOptions(), $dir) ?>
							</td>
							<td>
								<a href="#" class="delRow">Del</a>
							</td>
						</tr>
						<?php } ?>
						
						<tr>
							<td colspan="3">
								<a href="#" class="btnLink btnLinkLight addRow">Add</a>
							</td>
						</tr>
					</table>
					
					<input type="submit" value="Update" class="btnLink btnLinkLight icon iconReply" />
				</form>
			</div>
		</div>
		
		<div class="button">
			<?php echo linkTo("Reset All", array("action"=>"reset", "table"=>$foreign), 'class="icon iconReset"') ?>
		</div>
		
		<div class="button" style="float: left;">
			<label>Visible Items:</label>
			<?php echo select_box_tag("show", array("All Items", "Linked Items", "Unlinked Items")) ?>
		</div>
		
		<div class="clear"></div>
	</div>
	
	<div class="pod">
		<form method="post">
			<table class="dataGrid" data-gridTable="<?php echo $foreign ?>" data-headerUpdate="<?php echo routeUri(array("controller"=>"api", "action"=>"headerUpdate")) ?>" data-dragRows="<?php echo routeUri(array("controller"=>"api", "action"=>"rowOrder")) ?>">
				<thead>
					<tr>
						<th class="rowSelect"><input type="checkbox" class="checkAll" /></th>
						<th class="rowJoined"><img src="<?php echo publicUri("/images/icons/table_relationship.png") ?>" alt="Joined" /></th>
						<?php foreach ($columns as $column=>$sort) { ?>
						<th class="dragable <?php echo gridHeaderClass($foreign, $column, $sortColumns) ?>" data-column="<?php echo $column ?>"><span><?php echo Schema::columnName($foreign, $column) ?></span></th>
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
					<?php $self->renderPartials("row", $data, "item", array("table"=>$foreign, "columns"=>$columns)) ?>
				</tbody>
				
				<tfoot>
					<tr>
						<td colspan="999">
							<div class="newRow">
							<?php echo linkTo("New " . Schema::shortName($foreign), array("action"=>"edit", "table"=>$foreign), 'class="icon iconAddItem"') ?>
							</div>
							
						</td>
					</tr>
					
					<tr>
						<td colspan="999">
							<div class="gridButtons">
								<input type="submit" class="btnLink btnLinkLight btnLinkThin icon iconJoin" name="join" value="Join" />
								<input type="submit" class="btnLink btnLinkLight btnLinkThin icon iconDetach" name="detach" value="Detach" />
							</div>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>
		
		<div class="foot">
			<div class="pod podNested pad">
				<?php $self->renderPartial('/grid/nav', array("pager"=>$data)) ?>
			</div>
		</div>
	</div>
</div>

<?php
if ($self->canRenderPartial($foreign . "_row_form")) {
	$self->renderPartial($foreign . "_row_form");
}
?>
<?php $self->renderPartial('column_edit') ?>
