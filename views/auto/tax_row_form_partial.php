<form class="rowForm" method="post" action="<?php echo routeUri(array("controller"=>"api", "action"=>"rowInsert")) ?>">
	<input type="hidden" name="table" value="<?php echo $table ?>" />
	
	<label>Rate</label>
	<input type="text" name="item[rate]" value="" />
	
	<input type="submit" value="Save" class="btnLink btnLinkThin icon iconReply" />
</form>
