<li class="<?php echo message_cls($thread, $thread_num, $thread_total) ?>">
	<div class="preview">
		<a href="<?php echo routeUri(array("controller"=>"forum", "action"=>"message", "id"=>$thread->id)) ?>" class="<?php echo preview_cls($thread, $replies) ?>"><?php echo $thread->message ?></a> : <?php echo message_user_link($thread->name) ?>
	</div>
	<div class="full">
		<?php if ($thread->avatar) { ?>
		<img src="images/placeholders/<?php echo $thread->avatar ?>" alt="" />
		<?php } ?>
		<div class="content">
			<div class="reply">
				<?php if (User::hasRole(Session::user()->userId, AccessRole::ADMIN)) { ?>
				<a href="<?php echo routeUri(array("controller"=>"forum", "action"=>"delete", "id"=>$thread->id)) ?>" class="btnLink btnLinkMini iconThreadDel">Delete</a>
				<?php } ?>
				<a href="<?php echo routeUri(array("controller"=>"forum", "action"=>"message", "id"=>$thread->id)) ?>?reply=true" class="btnLink btnLinkMini iconReply">Reply</a>
			</div>
			<div class="author">By: <?php echo message_user_link($thread->name) ?></div>
			<div class="text">
				<?php echo $thread->message ?>
			</div>
		</div>
		<div class="clear"></div>
		<?php if (message_active($thread)) { $self->renderPartial('post'); } ?>
	</div>
	
	<?php if (count($replies->byGroup("parentId", $thread->id))) { ?>
	<ol class="children">
		<?php $self->renderPartials("message", $replies->byGroup("parentId", $thread->id), "thread", array("replies"=>$replies)) ?>
	</ol>
	<?php } ?>
</li>