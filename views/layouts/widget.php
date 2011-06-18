<!DOCTYPE HTML>

<html>
	<head>
		<title>Data Grid/Table Test</title>
		
		<?php $self->headerContent() ?>
	</head>
	
	<body>
		<div id="pageHeader">
			<div class="buttons">
				<a href="#" class="iconLarge iconMainMenu closePanel">Close</a>
			</div>
		</div>
		
		<div id="widgetBody">
				<?php echo $body_content ?>
		</div>
		
		<div id="ajaxThrobber">
			<ul class="content">
			</ul>
		</div>
		
		<div id="pageJavascripts">
			<?php $self->footerContent() ?>
		</div>
	</body>
</html>
