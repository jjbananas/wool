<form action="<?php echo routeUri(array("controller"=>"api", "action"=>"keySearch")) ?>" method="post" class="selectionGrid">
	<h3>Search: <span class="searchTarget">Product Id</span></h3>
	
	<div class="searchBar">
		<input type="hidden" name="table" value="" class="searchTable" />
		<input type="text" name="search" value="Search or #ID" />
		<input type="image" src="<?php echo baseUri('/images/icons/search.png') ?>" value="Search" />
		<a href="#close"><img src="<?php echo baseUri('/images/icons/close.png') ?>" alt="Close" title="Close" /></a>
	</div>
	
	<div class="results">
		<table>
			<tr>
				<td>No matches found</td>
			</tr>
		</table>
	</div>
</form>
