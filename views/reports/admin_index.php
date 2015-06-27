<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li>Reports</li>
	</ol>
</div>

<div class="pad">
	<h1>Reports</h1>
	<p>Reports allow you to easily track the data in your system. Create and view custom reports.</p>
</div>

<div class="pad">
	<div class="pad">
		<h2>Shop &amp; Order Reports</h2>

		<div class="groupButtons">
			<?php echo linkTo("Manage", array("action"=>"edit", "group"=>1), 'class="icon iconEdit btnLink btnLinkThin"') ?>
		</div>
	</div>

	<div class="clear"></div>

	<div class="pod">
		<div class="body">
			<div class="report">
				<div class="pad">
					<?php echo linkTo("Most Popular Products", array("action"=>"view", "id"=>1)) ?>
					<span class="desc">Top 50 most popular products</span>
				</div>
			</div>

			<div class="report">
				<div class="pad">
					<?php echo linkTo("Payment Report", array("action"=>"view", "id"=>1)) ?>
					<span class="desc">Payments and other transactions taken against your payment gateway.</span>
				</div>
			</div>
		</div>
	</div>
</div>
