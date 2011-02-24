<!DOCTYPE HTML>

<html>
	<head>
		<title>Data Grid/Table Test</title>
		
		<?php $self->headerContent() ?>
	</head>
	
	<body>
		<div id="pageCanvas">
			<div id="pageHeader">
				<div class="user">
					<?php echo linkTo(Session::user()->name, array("controller"=>"user", "id"=>Session::user()->userId), 'class="icon iconUser"') ?>
					<a href="<?php echo Request::uri(array("action"=>"logout")) ?>" class="icon iconLogout" title="Logout"></a>
				</div>
				
				<div class="buttons">
					<?php echo linkTo("Main Menu", array("controller"=>"auto", "action"=>"index"), 'class="iconLarge iconMainMenu"') ?>
				</div>
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
