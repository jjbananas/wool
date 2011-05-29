<?php
	$self->js("/components/Jcrop/jquery.Jcrop.js");
	$self->css("/components/Jcrop/jquery.Jcrop.css");
?>
<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("controller"=>"auto", "action"=>"index")) ?></li>
		<li>Image</li>
	</ol>
</div>

<div class="pad">
	<?php echo linkTo("Upload Images", array("action"=>"upload"), 'class="btnLink icon iconAddItem"') ?>
</div>

<div class="pad">
	<div class="pod">
		<div class="head">
			<h2 class="icon iconEdit">Image Archive</h2>
		</div>
		
		<div class="body">
			<?php foreach ($images as $image) { ?>
			<div class="image">
				<div class="pad">
					<div class="pod podNested">
						<a href="<?php echo publicUri('/uploads/images') . $image->file ?>">
							<span class="title"><?php echo $image->title ?></span>
							<img src="<?php echo routeUri(array("portal"=>"default", "action"=>"thumbnail", "uri"=>"sx-158__" . $image->file)) ?>" />
							<span class="size"><?php echo byteUnits(filesize(publicPath('/uploads/images') . $image->file)) ?></span>
							<span class="date">[<?php echo date('Y-m-d', filemtime(publicPath('/uploads/images') . $image->file)) ?>]</span>
						</a>
					</div>
				</div>
			</div>
			<?php } ?>
			
			<div class="clear"></div>
		</div>
		
		<div class="foot">
			<div class="pod podNested pad">
				<?php $self->renderPartial('/grid/nav', array("pager"=>$images)) ?>
			</div>
		</div>
	</div>
</div>
