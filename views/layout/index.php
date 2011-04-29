<?php
	$self->css('/components/simpledit/simpledit.css');
	$self->js('/components/simpledit/simpledit.js');
?>
<div class="container">
	<?php $self->renderPartial("layout", array("areas"=>$layoutAreas, "page"=>$page)) ?>
</div>