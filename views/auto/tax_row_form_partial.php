<?php echo renderNotices() ?>
<form class="rowForm" method="post" action="<?php echo routeUri(array("controller"=>"api", "action"=>"rowInsert")) ?>">
	<input type="hidden" name="table" value="<?php echo $table ?>" />
	
	<label>Name</label>
	<?php echo text_field($item, "item", "name") ?>
	
	<label>Rate</label>
	<?php echo text_field($item, "item", "rate") ?>
	
	<input type="submit" value="Save" class="btnLink btnLinkThin icon iconReply" />
</form>
