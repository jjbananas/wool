<h1>Directory Check</h1>

<p>Wool needs to be able to save certain files during operation.</p>

<p>If any of the directories below is flagged as a problem please check the documentation about setting file permissions.</p>

<div class="pod">
	<div class="head">
		<h2>Checking Writable Directories<h2>
	</div>

	<div class="body">
		<table class="dataGrid checkList">
			<tr>
				<th>Name</th>
				<th>Writable?</th>
			</tr>

			<?php foreach ($dirs as $dir=>$writable) { ?>
			<tr>
				<td><?php echo $dir ?></td>
				<td><img src="install/images/<?php echo $writable ? "tick" : "cross" ?>.png" /></td>
			</tr>
			<?php } ?>
		</table>
	</div>
</div>

<a class="btnLink" href="?page=3">Continue</a>