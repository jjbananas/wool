<?php if (User::hasRole(Session::user()->userId, AccessRole::ADMIN)) { ?>
<div class="toolbar">
	<div class="reply">
		<a href="<?php echo routeUri(array("controller"=>"article", "action"=>"edit", "location"=>$page->location)) ?>" class="btnLink iconEdit">Edit</a>
		<a href="<?php echo routeUri(array("controller"=>"article", "action"=>"history", "location"=>$page->location)) ?>" class="btnLink iconHistory">History</a>
		<a href="<?php echo routeUri(array("controller"=>"article", "action"=>"delete", "location"=>$page->location)) ?>" class="btnLink iconThreadDel">Delete</a>
	</div>
</div>
<?php } ?>

<div class="container">
	<div class="span-14">
		<div class="span-12 last push-1">
			<h1><?php echo $page->title ?></h1>
			
			<?php echo markdown($page->content) ?>
		</div>
	</div>
	
	<div class="span-9 sign-bump last">
		<div style="height: 300px"></div>
		<div id="sidebar">
			<h1 class="replace-text">Community Latest</h1>
			
			<div class="bd">
				<table>
					<tr class="even">
						<td><img src="images/placeholders/gravatar.png" alt="Tony Marklove's Logo" /></td>
						<td>
							<span>What is the easiest way to get to the last level of the game? I tried to take the key but it wasn't possible.</span> <a href="" class="author">Tony Marklove</a>
						</td>
					</tr>
					<tr class="odd">
						<td><img src="images/placeholders/gravatar.png" alt="Tony Marklove's Logo" /></td>
						<td>
							<span>What is the easiest way to get to the last level of the game? I tried to take the key but it wasn't possible.</span> <a href="" class="author">Tony Marklove</a>
						</td>
					</tr>
					<tr class="even">
						<td><img src="images/placeholders/gravatar.png" alt="Tony Marklove's Logo" /></td>
						<td>
							<span>What is the easiest way to get to the last level of the game? I tried to take the key but it wasn't possible.</span> <a href="" class="author">Tony Marklove</a>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>