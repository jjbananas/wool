<div class="container">
	<div class="span-14 last">
		<h1>Page History</h1>
		
		<?php if (!count($history)) { ?>
		<p>No history for the current page.</p>
		<?php } else { ?>
		<table class="stdTbl">
			<thead>
				<th>Date</th>
				<th>Author</th>
				<th></th>
			</thead>
			
			<?php foreach ($history as $rev) { ?>
			<tr>
				<td><?php echo ($rev->publishedOn) ?></td>
				<td><?php echo $rev->name ?></td>
				<td><?php echo linkTo('View', baseUri($location . "?revision=" . $rev->articleRevisionId)) ?></td>
			</tr>
			<?php } ?>
		</table>
		<?php } ?>
	</div>
</div>