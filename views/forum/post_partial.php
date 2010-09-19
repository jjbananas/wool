<?php if (Session::loggedIn()) { ?>
<div id="replyBox">
	<?php echo renderNotices() ?>
	
	<form method="post" action="">
		<label>New Message</label>
		<textarea name="message[message]" rows="10" cols="60"></textarea>
		<div>
			<input class="btnLink" type="submit" value="Post" />
		</div>
	</form>
</div>
<?php } else { ?>
<div id="replyBox">
	<p>You need to log in first</p>
</div>
<?php } ?>