<!DOCTYPE HTML>

<html>
	<head>
		<title>Shaded Game Studios</title>
		
		<link rel="stylesheet" href="css/print.css" type="text/css" media="print"> 
		<!--[if IE]>
		<link rel="stylesheet" href="css/ie.css" type="text/css" media="screen, projection">
		<![endif]-->
		
		<?php
			$self->css("/css/reset.css");
			$self->css("/css/screen.css");
			$self->css("/css/grids.css");
			$self->css("/css/style.css");
		?>
		
		<?php $self->headerContent() ?>
	</head>
	
	<body>
		<div id="editHeaderWrapper">
			<div id="editHeader">
				<div class="editContainer">
				</div>
			</div>
		</div>
		
		<div id="header">
			<div class="nav">
				<div class="container">
					<div class="userStatus">
						<?php if (Session::loggedIn()) { ?>
						Welcome, <?php echo linkTo(Session::user()->name, array("controller"=>"user", "action"=>"index")) ?> | <?php echo linkTo('Logout', array("controller"=>"user", "action"=>"logout")) ?>
						<?php } else { ?>
						<?php echo linkTo('Login or Register', array("controller"=>"user", "action"=>"login")) ?>
						<?php } ?>
					</div>
					
					<ul>
						<li <?php echo activeUri("/", true) ?>><?php echo linkTo('Home', baseUri('/')) ?></li>
						<li <?php echo activeUri("/games") ?>><?php echo linkTo('Games', array("controller"=>"games", "action"=>"index"), activeRoute($self, "")) ?></li>
						<li <?php echo activeRoute($self, "blog") ?>><?php echo linkTo('Blog', array("controller"=>"blog", "action"=>"index")) ?></li>
						<li <?php echo activeRoute($self, "forum") ?>><?php echo linkTo('Forum', array("controller"=>"forum", "action"=>"index")) ?></li>
						<li <?php echo activeUri("/support") ?>><?php echo linkTo('Support', array("controller"=>"support", "action"=>"index")) ?></li>
						<li <?php echo activeUri("/about") ?>><?php echo linkTo('About', array("controller"=>"about", "action"=>"index")) ?></li>
					</ul>
				</div>
				
				<div class="clear"></div>
			</div>
				
			<div id="headerLogoRow" class="container">
				<div id="mainLogo">Shaded Game Studios</div>
			</div>
		</div>
		
		<div id="body">
			<?php echo $body_content ?>
		</div>
		
		<div id="footer">
			<div class="header">
				<div class="container">
					<h2 class="replace-text">Site Map</h2>
					
					<div id="sign"><span></span></div>
				</div>
			</div>
			
			<div class="nav container">
				<div class="line">
					<div class="span-5 push-1">
						Games
						<ul>
							<li><a href="">Inside Track</a></li>
							<li><a href="">Melée3D</a></li>
						</ul>
					</div>
					
					<div class="span-6">
						Support
						<ul>
							<li><a href="">Inside Track Support</a></li>
							<li><a href="">Support Forum</a></li>
						</ul>
					</div>
					
					<div class="span-6">
						Blog
						<ul>
							<li><a href="test">An Introduction to Shaded</a></li>
							<li><a href="">Support Forum</a></li>
						</ul>
					</div>
					
					<div class="last span-6">
						Technology
						<ul>
							<li><a href="">Developers</a></li>
							<li><a href="">Level Design</a></li>
							<li><a href="">Licensing</a></li>
							<li><a href="">Graphic Design</a></li>
						</ul>
					</div>
				</div>
				
				<div class="line">
					<div class="span-5 push-1">
						Forums
						<ul>
							<li><a href="">Visit Forums</a></li>
						</ul>
					</div>
					
					<div class="span-6">
						Stats
						<ul>
							<li><a href="">Game Stats</a></li>
							<li><a href="">Web Stats</a></li>
						</ul>
					</div>
					
					<div class="last span-6">
						Shop
						<ul>
							<li><a href="">Boxed Games</a></li>
							<li><a href="">T-Shirts</a></li>
							<li><a href="">Mugs</a></li>
						</ul>
					</div>
				</div>
			</div>
			
			<div id="footerCopyright">
				<div class="container">
					Copyright &copy; Shaded Game Studios
				</div>
			</div>
		</div>
	</body>
	
	<div id="javascripts">
		<?php $self->footerContent() ?>
	</div>
</html>
