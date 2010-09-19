<?php
	$self->css('/css/forum.css');
	$self->js('/js/forum.js');
?>
<div class="breadcrumbs">
	<ol>
		<li><?php echo linkTo('Home', '~/') ?></li>
		<li><?php echo linkTo('Forum', '~/forum') ?></li>
		<li>A History</li>
	</ol>
</div>

<div class="container">
	<div class="span-20">
		<div class="span-20 last">
			<ol class="forumThreads">
				<?php $self->renderPartial("message") ?>
			</ol>
		</div>
	</div>
</div>
