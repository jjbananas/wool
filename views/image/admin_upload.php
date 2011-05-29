<?php
	$self->js("/components/Jcrop/jquery.Jcrop.js");
	$self->css("/components/Jcrop/jquery.Jcrop.css");
?>
<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("controller"=>"auto", "action"=>"index")) ?></li>
		<li><?php echo linkTo("Image", array("action"=>"index")) ?></li>
		<li>Upload</li>
	</ol>
</div>

<div class="pad">
	<form method="post" enctype="multipart/form-data">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Image Upload</h2>
			</div>
			
			<div class="body">
				<div class="imageUpload pad">
					<p>Select the file(s) you wish to upload.</p>
					
					<input type="file" name="file" />
				</div>
				
				<div class="imageCanvas">
				</div>
				
				<div class="imageDetails">
					<div class="s1of4">
						<div class="editPanel">
							<div class="input">
								<label>Title</label>
								<input type="text" name="image[title]" />
							</div>
						</div>
					</div>
					
					<div class="s3of4">
						<div class="pad">
							<div class="pod podNested options advanced">
								<div class="pad">
									<input type="checkbox" checked="checked" class="keepAspect" /><label>Keep Aspect Ratio</label>
									<input type="checkbox" checked="checked" class="restrictMin" /><label>Restrict Min</label>
									<input type="checkbox" checked="checked" class="restrictMax" /><label>Restrict Max</label>
								</div>
							</div>
						</div>
						
						<div class="s1of4">
							<div class="editPanel coords advanced">
								Crop:
								<div>
									<label>X:</label><input type="text" name="image[x]" size="2" />
									<label>Y:</label><input type="text" name="image[y]" size="2" />
								</div>
								<div class="clear">
									<label>W:</label><input type="text" name="image[w]" size="2" />
									<label>H:</label><input type="text" name="image[h]" size="2" />
								</div>
								
								<div class="clear"></div>
							</div>
						</div>
						
						<div class="s1of4">
							<div class="pad">
								<div class="target min">
									Min: <a href="#" class="useTarget"><img src="<?php echo baseUri("/images/icons/textfield_add.png") ?>" alt="Use Max" /></a>
									<div>
										<label>W:</label><span class="width">&#8734;<?php echo $imageParams["min"]["w"] ?></span>
										<label>H:</label><span class="height"><?php echo $imageParams["min"]["h"] ?></span>
									</div>
									<div class="clear"></div>
								</div>
								<div class="target max">
									Max: <a href="#" class="useTarget"><img src="<?php echo baseUri("/images/icons/textfield_add.png") ?>" alt="Use Max" /></a>
									<div>
										<label>W:</label><span class="width"><?php echo $imageParams["max"]["w"] ?></span>
										<label>H:</label><span class="height"><?php echo $imageParams["max"]["h"] ?></span>
									</div>
									<div class="clear"></div>
								</div>
								<div class="target aspect">
									Forced Aspect:
									<div>
										<span class="width"><?php echo $imageParams["aspect"] ? "Yes" : "No" ?></span>
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
						
						<div class="s1of2">
							<div class="pad">
								<div class="target result">
									Result:
									<div>
										<label>W:</label><span class="width">640</span>
										<label>H:</label><span class="height">480</span>
									</div>
									<div class="clear"></div>
								</div>
								
								<div class="note">
									Note: This may cause stretching.
								</div>
							</div>
						</div>
					</div>
					
					<div class="clear"></div>
				</div>
				
				<div class="imageQueue advanced">
					<div class="queueItem queuePlaceholder">
						<div class="queueThumb">
							<img src="<?php echo baseUri("/images/image/upload-target.png") ?>" />
						</div>
						
						<div class="queueInfo">
							<div class="title">
								<span>&nbsp;</span>
							</div>
							<div class="progressBar">
								<span class="progress"></span>
							</div>
						</div>
					</div>
					
					<div class="clear"></div>
				</div>
			</div>
			
			<div class="foot">
				<input type="submit" class="btnLink icon iconSave" value="Save" />
			</div>
		</div>
	</form>
</div>

<script>
var imageParams = {
	min: {w: <?php echo $imageParams["min"]["w"] ?>, h: <?php echo $imageParams["min"]["h"] ?>},
	max: {w: <?php echo $imageParams["max"]["w"] ?>, h: <?php echo $imageParams["max"]["h"] ?>},
	aspect: <?php echo $imageParams["aspect"] ?>
};
</script>
