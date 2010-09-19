<div class="container bodyMargin">
	<div class="span-14 last">
		<div class="news section">
			<?php echo renderNotices() ?>
			
			<form method="post">
				<?php echo label("article_revisions", "title") ?>
				<?php echo text_field($page, "page", "title", array("size"=>80)) ?>
				
				<?php echo label("article_revisions", "content") ?>
				<?php echo text_area($page, "page", "content", array("rows"=>50, "cols"=>80)) ?>
				
				<input type="submit" class="btnLink" value="Save" />
			</form>
		</div>
</div>