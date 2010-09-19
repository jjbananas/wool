<div class="container bodyMargin">
	<div class="span-14">
		<form method="post">
			<p>Are you sure you want to delete this post (and sub-thread)?</p>
			<input type="submit" class="btnLink" value="Confirm Delete" />
		</form>
		
		<div class="span-12 last push-1">
			<h1><?php echo $page->title ?></h1>
			
			<?php echo markdown($page->content) ?>
		</div>
	</div>
</div>