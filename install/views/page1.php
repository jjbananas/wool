<h1>Installed PHP Extensions</h1>

<p>Wool depends on a few <strong>commonly available</strong> PHP extensions.</p>

<p>If any of the extensions below are not available you will need to install them before continuing.</p>

<div class="pod">
	<div class="head">
		<h2>Checking Extensions<h2>
	</div>

	<div class="body">
		<table class="dataGrid checkList">
			<tr>
				<th>Name</th>
				<th>Installed?</th>
			</tr>

			<?php foreach ($extensions as $ext=>$name) { ?>
			<tr>
				<td><?php echo $name ?> (<?php echo $ext ?>)</td>
				<td><img src="install/images/<?php echo extension_loaded($ext) ? "tick" : "cross" ?>.png" /></td>
			</tr>
			<?php } ?>
		</table>
	</div>
</div>

<a class="btnLink" href="?page=2">Continue</a>