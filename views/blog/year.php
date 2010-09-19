<div class="breadcrumbs">
	<ol>
		<li><?php echo linkTo('Home', baseUri('/')) ?></li>
		<li><?php echo linkTo('Blog/Articles', array("controller"=>"blog", "action"=>"index")) ?></li>
		<li><?php echo $year ?></li>
	</ol>
</div>

<div class="container">
	<div class="span-14">
		<div class="span-12 last push-1">
			<?php foreach ($articles as $article) { ?>
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
			<?php } ?>
		</div>
	</div>
</div>
