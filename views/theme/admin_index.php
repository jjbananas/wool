<?php
	$self->js("/components/colorpicker/js/colorpicker.js");
	$self->css("/components/colorpicker/css/colorpicker.css");
?>
<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("controller"=>"auto", "action"=>"index")) ?></li>
		<li>Theme</li>
	</ol>
</div>

<div class="pad">
	<form method="post">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Theme Configuration</h2>
			</div>
			
			<div class="body splitBody">
				<div class="s1of2">
					<div class="editPanel">
						<div class="input">
							<label for="foreground">Foreground</label>
							<?php
								echo color_field_tag("foreground");
							?>
						</div>
						
						<div class="input">
							<label for="background">Background</label>
							<?php
								echo color_field_tag("background");
							?>
						</div>
					</div>
				</div>
				
				<div class="s1of2">
					<div class="pad">
						<img src="<?php echo routeUri(array("action"=>"previewImage")) ?>" alt="" />
					</div>
				</div>
				
				<div class="clear"></div>
			</div>
			
			<div class="foot">
				<input type="submit" class="btnLink icon iconSave" value="Save" />
			</div>
		</div>
	</form>
</div>
