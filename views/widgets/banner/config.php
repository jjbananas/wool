<form method="post" class="widgetConfig">
	<div class="pad">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Banner</h2>
			</div>
			
			<div class="body splitBody">
				<div class="s1of2">
					<div class="editPanel">
						<?php echo renderNotices() ?>
						
						<div class="input foreign" data-references="image_collection">
							<label for="collection">Image Collection</label>
							<?php echo hidden_field_tag("collection") ?>
							<a href="" class="choice icon iconKeyLink">Image Collection</a>
						</div>
					</div>
				</div>
				
				<div class="s1of2">
					<div class="padh">
						<div class="selectionGridSpacer">
						</div>
					</div>
				</div>
				
				<div class="clear"></div>
			</div>
			
			<div class="foot">
				<input type="submit" class="btnLink icon iconAddItem" value="Update" />
			</div>
		</div>
	</div>
</form>

<?php $self->renderPartial('/auto/selection_grid') ?>
