<div class="pod podOverlay" id="colunmEdit">
	<form action="<?php echo routeUri(array("controller"=>"api", "action"=>"columnUpdate")) ?>" method="post">
		<div class="body editPanel">
			<label>Login URL</label>
			<input type="text" name="value" />
		</div>
		
		<div class="foot">
			<input type="submit" value="OK" class="btnLink icon iconSave" />
			<a href="#">Cancel</a>
		</div>
	</form>
</div>
