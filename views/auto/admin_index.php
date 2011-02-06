<div class="pad">
	<h1>Table List</h1>
	<p>The following tables are available in your application.</p>
</div>

<div class="pad">
	<ul>
		<?php foreach ($tables as $table) { ?>
		<li><?php echo linkTo(WoolTable::displayName($table), array("table"=>$table)) ?></li>
		<?php } ?>
	</ul>
</div>
