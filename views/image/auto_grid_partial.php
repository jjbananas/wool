<div class="pod">
	<div class="head">
		<h2 class="icon iconEdit">Image Archive</h2>
	</div>
	
	<div class="body">
		<?php foreach ($grid as $num=>$image) { ?>
		<div class="image">
			<div class="pad">
				<div class="pod podNested">
					<a href="<?php echo publicUri('/uploads/images') . $image->file ?>">
						<span class="title"><?php echo $image->title ?></span>
						<img src="<?php echo routeUri(array("portal"=>"default", "controller"=>"image", "action"=>"thumbnail", "uri"=>"sx-158__" . $image->file)) ?>" />
						<span class="size"><?php echo byteUnits(filesize(publicPath('/uploads/images') . $image->file)) ?></span>
						<span class="date">[<?php echo date('Y-m-d', filemtime(publicPath('/uploads/images') . $image->file)) ?>]</span>
					</a>
				</div>
			</div>
		</div>
		<?php echo rowClear($num, 5) ?>
		<?php } ?>
		
		<div class="clear"></div>
	</div>
	
	<div class="foot">
		<div class="pod podNested pad">
			<?php $self->renderPartial('/grid/nav', array("pager"=>$grid)) ?>
		</div>
	</div>
</div>
