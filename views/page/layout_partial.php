<?php
$num = 0;
foreach ($areas->children as $name=>$area) {
?>
<div id="layout-<?php echo $name ?>" class="<?php echo layoutClass($area, $num == count($areas->children)-1) ?>">
	<div class="layoutPad <?php echo $page->editableWidget($name) ? 'editable' : '' ?>">
		<?php $self->renderPartial("layout", array("areas"=>$area, "page"=>$page)) ?>
		
		<?php echo $page->widgetFor($name) ?>
	</div>

	<div class="clear"></div>
</div>
<?php
	$num++;
}
?>
