<?php
	$self->css('/css/forum.css');
	$self->js('/js/forum.js');
?>
<div class="breadcrumbs">
	<ol>
		<li><?php echo linkTo('Home', baseUri('/')) ?></li>
		<li><?php echo linkTo('Forum', array("controller"=>"forum", "action"=>"index")) ?></li>
		<li>Delete Message/Thread</li>
	</ol>
</div>

<div class="container bodyMargin">
	<div class="span-20">
		<div class="span-20 last">
			<form method="post">
				<p>Are you sure you want to delete this post (and sub-thread)?</p>
				<input type="submit" class="btnLink" value="Confirm Delete" />
			</form>
			
			<ol class="forumThreads">
				<?php
				$self->renderPartial("message", array(
					"thread"=>$replies->by("id", $message->id),
					"thread_num"=>1,
					"thread_total"=>1,
					"replies"=>$replies
				))
				?>
			</ol>
		</div>
	</div>
</div>
