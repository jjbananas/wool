
<div class="pad">
	<form method="post">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Add/Edit Example Product #1</h2>
			</div>
			
			<div class="body editPanel">
				<?php foreach ($columns as $column) { ?>
				<div class="input">
					<?php echo label($table, $column) ?>
					<?php echo text_field($item, "item", $column) ?>
				</div>
				<?php } ?>
				
				<?php /*
				<div class="input foreign">
					<label for="belongs">Belongs To</label>
					<input type="hidden" name="belongs" value="0" />
					<a href="" class="choice">None Selected</a>
				</div>
				
				<div class="pad">
					<div class="pod podNested selectionGrid">
						<div class="body">
							<input type="text" name="search" value="Search or #ID" />
							
							<div class="results">
								<table>
									<tr>
										<td>Tony Marklove</td>
										<td class="select"><a href="#"><img src="images/forms/select-link.png" title="Select this match" alt="Select" /></a></td>
									</tr>
									<tr>
										<td>Tony Marklove</td>
										<td class="select"><a href="#"><img src="images/forms/select-link.png" title="Select this match" alt="Select" /></a></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
				
				*/
				?>
			</div>
			
			<div class="foot">
				<input type="submit" class="btnLink" value="Save" />
			</div>
		</div>
	</form>
</div>
