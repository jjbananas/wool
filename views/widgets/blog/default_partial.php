<?php foreach ($articles as $article) { ?>
<div class="news section">
	<h1><?php echo $article->title ?></h1>
	
	<div class="item">
		<p class="date"><?php echo published_date($article) ?></p>
		<p class="author"><?php echo $article->name ?></p>
		
		<?php echo $article->excerpt ?>
		
		<div class="ft">
			<?php echo linkTo('Read More', blogUri($article), 'class="btnLink"') ?></a>
		</div>
	</div>
</div>
<?php } ?>