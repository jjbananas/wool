<div class="breadcrumbs">
	<ol>
		<li><?php echo linkTo('Home', baseUri('/')) ?></li>
		<li><?php echo linkTo('Forum', array("controller"=>"forum", "action"=>"index")) ?></li>
		<li>New Message</li>
	</ol>
</div>

<div class="container bodyMargin">
	<div class="span-20">
		<div class="span-20 last">
			<?php $self->renderPartial('post') ?>
		</div>
	</div>
</div>