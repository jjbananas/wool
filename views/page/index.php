<?php
	$self->css('/components/simpledit/simpledit.css');
	$self->js('/components/simpledit/simpledit.js');
	//$self->js('/components/ckeditor/ckeditor.js');
	//$self->js('/components/ckeditor/adapters/jquery.js');
?>
<div class="container">
	<?php $self->renderPartial("layout", array("areas"=>$layoutAreas, "page"=>$page)) ?>
</div>