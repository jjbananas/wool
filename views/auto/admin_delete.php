<div class="pad">
	<form method="post">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Delete <?php echo WoolTable::displayName($table) ?> #<?php $u = WoolTable::uniqueColumn($table); echo $item->$u ?></h2>
			</div>
			
			<div class="body">
				<div class="pad">
					<p>Are you sure you want to delete this item?</p>
				</div>
			</div>
			
			<div class="foot">
				<div class="padh">
					<a href="<?php echo Request::back() ?>">&laquo; No Thanks</a>
					&nbsp;
					<input type="submit" class="btnLink icon iconDelete" value="Delete" />
				</div>
			</div>
		</div>
	</form>
</div>
