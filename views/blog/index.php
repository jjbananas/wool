<div class="breadcrumbs">
	<ol>
		<li><?php echo linkTo('Home', baseUri('/')) ?></li>
		<li>Blog/Articles</li>
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
						<?php echo linkTo('Read More', blogUri($article), 'class="btnLink"') ?></a>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
