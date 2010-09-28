<div class="container bodyMargin">
	<div class="span-14 last">
		<div class="news section">
			<?php echo renderNotices() ?>
			
			<form method="post">
				<?php echo label("article_revisions", "title") ?>
				<?php echo text_field($revision, "revision", "title", array("size"=>80)) ?>
				
				<?php echo label("articles", "location") ?>
				<p style="margin: 0;"><small>Leave blank for auto-generated location based on name</small></p>
				<?php echo text_field($article, "article", "location", array("size"=>40)) ?>
				
				<?php echo label("article_revisions", "content") ?>
				<?php echo text_area($revision, "revision", "content", array("rows"=>50, "cols"=>80)) ?>
				
                <?php echo label("article_revisions", "excerpt") ?>
				<p style="margin: 0;"><small>Excerpt will be created from content, if left blank</small></p>
				<?php echo text_area($revision, "revision", "excerpt", array("rows"=>10, "cols"=>80)) ?>
				
				<input type="submit" class="btnLink" value="Save" />
			</form>
		</div>
	</div>
</div>
