<div class="container bodyMargin">
	<div class="span-20">
		<h1>Public profile of <?php echo $user->name ?></h1>
		
		<h2>Recent Messages</h2>
		<?php $self->renderPartial('/forum/summary', array("messages"=>$threads)) ?>
	</div>
</div></h1>