<?php
	$self->css('/css/forum.css');
	$self->js('/js/forum.js');
?>
<div class="breadcrumbs">
	<ol>
		<li><?php echo linkTo('Home', baseUri('/')) ?></li>
		<li>Forum</li>
	</ol>
</div>

<div class="container">
	<div class="span-20">
		<div class="span-20 last">
			<a href="<?php echo routeUri(array("action"=>"create")) ?>" class="btnLink">New Post</a>
			<?php $self->renderPartial('post') ?>
			
			<ol class="forumThreads">
				<?php $self->renderPartials("message", $threads, "thread", array("replies"=>$replies)) ?>
			</ol>
			
			<div style="text-align: right;">
				<?php
				echo navPrev($threads, 'class="btnLink"');
				echo navNext($threads, 'class="btnLink"');
				?>
			</div>
		</div>
	</div>
</div>
