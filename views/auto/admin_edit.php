<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo linkTo(Schema::displayName($table), array("action"=>"table", "table"=>$table)) ?></li>
		<li>Add/Edit <?php echo Schema::displayName($table) ?> #<?php $u = Schema::uniqueColumn($table); echo $item->$u ?></li>
	</ol>
</div>

<div class="pad">
	<form method="post">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Add/Edit <?php echo Schema::displayName($table) ?> #<?php $u = Schema::uniqueColumn($table); echo $item->$u ?></h2>
			</div>
			
			<div class="body splitBody">
				<div class="s1of2">
					<div class="editPanel">
						<?php foreach ($columns as $column=>$col) { ?>
						<?php if ($ref = Schema::columnIsKey($table, $column)) { ?>
						<div class="input foreign" data-references="<?php echo $ref ?>">
							<?php echo label($table, $column) ?>
							<?php echo hidden_field($item, "item", $column) ?>
							<a href="" class="choice icon iconKeyLink"><?php echo ($item->$column ? "{$item->$column}: {$item->title}" : 'None Selected') ?></a>
						</div>
						<?php } else { ?>
						<div class="input">
							<?php
								echo label($table, $column);
								echo auto_field($item, $table, $column);
							?>
						</div>
						<?php } ?>
						<?php } ?>
					</div>
				</div>
				
				<div class="s1of2">
					<div class="padh">
						<?php if ($item->$u) { ?>
						<div class="pod podToolbar podEditTools">
							<div class="pad">
								<?php echo linkTo('Delete', array("action"=>"delete", "table"=>$table, "id"=>$item->$u), 'class="btnLink btnLinkLight icon iconDelete"') ?>
								<?php if (Schema::tableHasHistory($table)) { ?>
								<?php echo linkTo('History', array("action"=>"history", "table"=>$table, "id"=>$item->$u), 'class="btnLink btnLinkLight icon iconHistory "') ?>
								<?php } ?>
							</div>
						</div>
						<?php } ?>
						
						<div class="selectionGridSpacer">
						</div>
					</div>
					
					<div class="pad derivedPanel">
						<?php foreach ($derivedColumns as $column=>$col) { ?>
						<div class="derived">
							<span class="column"><?php echo Schema::columnName($table, $column) ?></span>
							<span class="value"><?php echo $item->$column ?></span>
						</div>
						<?php } ?>
					</div>
				</div>
				
				<div class="clear"></div>
			</div>
			
			<div class="foot">
				<input type="submit" class="btnLink icon iconSave" value="Save" />
			</div>
		</div>
	</form>
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
