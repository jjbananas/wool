<!DOCTYPE HTML>

<html>
	<head>
		<title>Data Grid/Table Test</title>
		
		<?php $self->headerContent() ?>
	</head>
	
	<body>
		<div id="pageCanvas">
			<div id="pageHeader">
				<?php echo linkTo("Main Menu", array("controller"=>"auto", "action"=>"index"), 'class="iconLarge iconMainMenu"') ?>
			</div>
			
			<div id="pageBody">
				<?php echo $body_content ?>
			</div>
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
