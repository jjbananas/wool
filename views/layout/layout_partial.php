<?php foreach ($areas->children as $name=>$area) { ?>
<div id="layout-<?php echo $name ?>" class="<?php echo layoutClass($area) ?>">
	<div class="layoutPad">
		<?php $self->renderPartial("layout", array("areas"=>$area, "page"=>$page)) ?>
		
		<?php
		echo $page->widgetFor($name);
		echo $page->contentFor($name);
		?>
	</div>
</div>
<?php } ?>
