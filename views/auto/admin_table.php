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
	
	<?php $self->renderPartial("grid", array("table"=>$table, "grid"=>$grid)) ?>
</div>

<?php
if ($self->canRenderPartial("/{$table}/auto_row_form")) {
	$self->renderPartial("/{$table}/auto_row_form");
}
?>
<?php $self->renderPartial('column_edit') ?>
