<div class="pod podOverlay" id="columnEdit">
	<form action="<?php echo routeUri(array("controller"=>"api", "action"=>"columnUpdate")) ?>" method="post">
		<div class="body editPanel">
			<span class="labelLine"><label></label></span>

			<div class="inputTarget">
				<div class="text">
					<input type="text" name="value" />
				</div>
			</div>
		</div>
		
		<div class="foot">
			<input type="submit" value="OK" class="btnLink icon iconSave" />
			<a href="#">Cancel</a>
		</div>
	</form>
</div>
