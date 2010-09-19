<div class="container">
	<div class="span-20">
		<div class="span-20 last">
			<?php echo renderNotices() ?>
			<form method="post">
				<?php echo text_field($user, "user", "name") ?>
				<?php echo text_field($user, "user", "email") ?>
				<?php echo text_field($user, "user", "password") ?>
				
				<input type="submit" value="sign up" />
			</form>
		</div>
	</div>
</div>
