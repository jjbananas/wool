<?php
	//$self->css("/components/nivo/nivo-slider.css");
	//$self->js("/components/nivo/jquery.nivo.slider.js");
?>
<div id="slider">
	<?php foreach ($images as $image) { ?>
	<img src="<?php echo routeUri(array("portal"=>"default", "controller"=>"image", "action"=>"thumbnail", "uri"=>"sx-158__" . $image->file)) ?>" />
	<?php } ?>
</div>