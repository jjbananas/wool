<!DOCTYPE HTML>

<html>
	<head>
		<title>Data Grid/Table Test</title>
		
		<?php $self->headerContent() ?>
	</head>
	
	<body>
		<div id="pageCanvas">
			<div id="pageHeader">
				<?php if (Session::loggedIn()) { ?>
				<div class="user">
					<?php echo linkTo(Session::user()->name, array("controller"=>"user", "action"=>"view", "name"=>Session::user()->name), 'class="icon iconUser"') ?>
					<?php echo linkTo('', array("controller"=>"user", "action"=>"logout", "direct"=>Request::uriForDirect()), 'class="icon iconLogout" title="Logout"') ?>
				</div>
				<?php } ?>
				
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
