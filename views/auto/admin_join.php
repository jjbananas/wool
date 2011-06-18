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
	<?php $self->renderPartial("grid_filter", array("table"=>$foreign, "data"=>$grid)) ?>
	
	<?php $self->renderPartial("column_tools", array("table"=>$foreign, "grid"=>$grid)) ?>
	
	<?php $self->renderPartial("grid", array("table"=>$foreign, "grid"=>$grid)) ?>
</div>

<?php
if ($self->canRenderPartial($foreign . "_row_form")) {
	$self->renderPartial($foreign . "_row_form");
}
?>
<?php $self->renderPartial('column_edit') ?>
