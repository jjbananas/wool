<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo linkTo(Schema::displayName($table), array("action"=>"table", "table"=>$table)) ?></li>
		<li>Add/Edit <?php echo Schema::displayName($table) ?> #<?php $u = Schema::uniqueColumn($table); echo $item->$u ?></li>
	</ol>
</div>

<div class="pad">
	<?php $self->renderPartial('table_edit') ?>
</div>

<?php foreach ($foreign as $table=>$f) { ?>
<div class="pad">
	<div class="pod">
		<div class="head">
			<h2>Related <?php echo Schema::displayName($table) ?></h2>
		</div>
		
		<table class="dataGrid" data-gridTable="<?php echo $table ?>" data-headerUpdate="<?php echo routeUri(array("controller"=>"api", "action"=>"headerUpdate")) ?>" data-dragRows="<?php echo routeUri(array("controller"=>"api", "action"=>"rowOrder")) ?>">
			<thead>
				<tr>
					<th></th>
					<?php foreach ($f["columns"] as $column=>$sort) { ?>
					<th class="dragable <?php echo Schema::columnEditable($table, $column) ? "editable" : "" ?>" data-column="<?php echo $column ?>"><?php echo Schema::columnName($table, $column) ?></th>
					<?php } ?>
					<th width="1"></th>
				</tr>
			</thead>
			
			<tbody>
				<?php if (!count($f["data"])) { ?>
				<tr>
					<td colspan="999">No items found.</td>
				</tr>
				<?php } ?>
				<?php $self->renderPartials("row", $f["data"], "item", array("table"=>$table, "columns"=>$f["columns"])) ?>
			</tbody>
		</table>
		
		<div class="foot">
			<div class="pod podNested pad">
				<?php $self->renderPartial('/grid/nav', array("pager"=>$f["data"])) ?>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<?php $self->renderPartial('selection_grid') ?>

<script>
	var validators = <?php echo live_validators() ?>;
</script>

