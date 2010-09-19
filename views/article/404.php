<div class="container">
	<div class="span-14">
		<div class="span-12 last push-1">
			<div class="news section">
				<h1>What you are looking for can't be found</h1>
				<p>It is one of the misteries of the universe.</p>
			</div>
			
			<?php if (Session::loggedIn()) { ?>
			<div class="news section">
				<h1>Create this page in the Wiki</h1>
				<p><?php echo linkTo('Create page', array("controller"=>"article", "action"=>"edit", "location"=>Request::path(true))) ?></p>
			</div>
			<?php } ?>
		</div>
	</div>
</div>