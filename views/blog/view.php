<div class="breadcrumbs">
	<ol>
		<li><?php echo linkTo('Home', baseUri('/')) ?></li>
		<li><?php echo linkTo('Blog/Articles', array("controller"=>"blog", "action"=>"index")) ?></li>
		<li><?php echo blogYearLink($article) ?></li>
		<li><?php echo blogMonthLink($article) ?></li>
		<li>A History</li>
	</ol>
</div>

<?php if (User::hasRole(Session::user()->userId, AccessRole::ADMIN)) { ?>
<div class="toolbar">
	<div class="reply">
		<a href="<?php echo routeUri(array("controller"=>"blog", "action"=>"edit", "id"=>$article->postId)) ?>" class="btnLink iconEdit">Edit</a>
		<a href="<?php echo routeUri(array("controller"=>"blog", "action"=>"history", "id"=>$article->postId)) ?>" class="btnLink iconHistory">History</a>
		<a href="<?php echo routeUri(array("controller"=>"blog", "action"=>"delete", "id"=>$article->postId)) ?>" class="btnLink iconThreadDel">Delete</a>
	</div>
</div>
<?php } ?>

<div class="container">
	<div class="span-14">
		<div class="span-12 last push-1">
			<div class="news section">
				<h1><?php echo $article->title ?></h1>
				
				<div class="item">
					<p class="date"><?php echo published_date($article) ?></p>
					<p class="author"><?php echo $article->name ?></p>
					
					<?php echo $article->content ?>
					
					<div class="ft">
						<a href="" class="btnLink">Discussion</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="span-9 sign-bump last">
		<div id="sidebar">
			<h1 class="replace-text">Community Latest</h1>
			
			<div class="bd">
				<table>
					<?php foreach ($forumLatest as $thread) { ?>
					<tr class="even">
						<td><img src="<?php echo baseUri('/images/placeholders/' . $thread->avatar) ?>" alt="" /></td>
						<td>
							<a href="<?php echo routeUri(array("controller"=>"forum", "action"=>"message", "id"=>$thread->id)) ?>"><?php echo $thread->message ?></a> : <?php echo message_user_link($thread->name) ?>
						</td>
					</tr>
					<?php } ?>
				</table>
			</div>
		</div>
		
		<div id="sidebar">
			<h1>Related Articles</h1>
			
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
