<form method="post" class="widgetConfig">
	<div class="pad">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Content</h2>
			</div>
			
			<div class="body splitBody">
				<div class="s1of2">
					<div class="editPanel">
						<?php echo renderNotices() ?>
						
						<div class="input" data-references="image_collection">
							<label for="collection">Content</label>
							<?php echo text_area_tag("content", "") ?>
						</div>
					</div>
				</div>
			</div>
			
			<div class="foot">
				<input type="submit" class="btnLink icon iconAddItem" value="Update" />
			</div>
		</div>
	</div>
</form>
