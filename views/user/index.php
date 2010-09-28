<div class="container bodyMargin">
	<div class="span-20">
		<h1><?php echo $user->name ?></h1>
		<p>This is your personal profile</p>
		
		<h2>Recent Messages</h2>
		<?php $self->renderPartial('/forum/summary', array("messages"=>$threads)) ?>
	</div>
</div>