<?php
	$self->js('/components/nicEdit/nicEdit.js');
?>
<style>
#myInstance1 {
        border: 2px dashed #0000ff;
}
.editable {
	outline: 2px dashed #cccccc;
}
.nicEdit-selected {
        outline: 2px solid #d8d828 !important;
}

.nicEdit-panel {
	background-color: #292b2c !important;
}

.nicEdit-button {
	background-color: #292b2c !important;
}
</style>
<div class="container">
	<?php $self->renderPartial("layout", array("areas"=>$layoutAreas, "page"=>$page)) ?>
</div>