<!DOCTYPE HTML>

<html>
	<head>
		<title>Data Grid/Table Test</title>
		
		<?php $self->headerContent() ?>
	</head>
	
	<body>
		<div id="pageCanvas">
			<div id="pageHeader">
				
			</div>
			
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
