<div class="container">
	<div class="span-20">
		<div class="span-20 last" style="margin-top: 40px;">
			<?php echo renderNotices() ?>
			<form method="post">
				<label for="user">Email Address</label>
				<?php echo text_field_tag("user") ?>
				
				<label for="user">Password</label>
				<?php echo password_field_tag("pass") ?>
				
				<input type="submit" class="btnLink" value="login" />
			</form>
		</div>
	</div>
</div>