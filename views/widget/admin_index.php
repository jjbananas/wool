<script>
	var widgetApi = window.parent.WOOL.widgetApi;
	
	widgetApi.log("i'm back!");
</script>
<?php
	$self->js("/js/jquery.json-2.2.js");
	$self->js('/components/fancybox/jquery.fancybox-1.3.4.js');
	$self->css('/components/fancybox/jquery.fancybox-1.3.4.css');
?>
<div class="pad">
	<div class="pod">
		<div class="head">
			<h2 class="icon iconEdit">Layout</h2>
		</div>
		
		<div class="body splitBody">
			<div class="s1of2">
				<div class="editPanel">
					<?php echo renderNotices() ?>
				</div>
			</div>
			
			<div class="s1of2">
			</div>
			
			<div class="clear"></div>
		</div>
		
		<div class="foot">
			<?php echo linkTo("Save", array("action"=>"edit"), 'class="btnLink icon iconAddItem"') ?>
		</div>
	</div>
</div>
