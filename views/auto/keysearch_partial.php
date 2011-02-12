<table>
	<?php if (!count($matches)) { ?>
	<tr>
		<td>No matches found</td>
	</tr>
	<?php } ?>
	<?php foreach ($matches as $match) { ?>
	<tr>
		<td>
			<span>#<?php echo $match->id ?><?php echo isset($match->title) ? ": {$match->title}" : '' ?></span>
		</td>
		<td class="select"><a href="#use" data-id="<?php echo $match->id ?>" class="icon iconAccept">Use</a></td>
	</tr>
	<?php } ?>
</table>
