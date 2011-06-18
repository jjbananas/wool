<div class="columnTools">
	<div class="columnSelect button">
		<a href="#" class="icon iconColumn">Columns</a>
	
		<div class="pod podOverlay">
			<div class="head">
				<h2 class="icon iconColumn">Columns</h2>
			</div>
			
			<form method="post" action="<?php echo routeUri(array("controller"=>"api", "action"=>"columnSelect")) ?>" class="pad">
				<input type="hidden" name="direct" value="<?php echo Request::uriForDirect() ?>" />
				<input type="hidden" name="table" value="<?php echo $table ?>" />
				
				<table>
					<?php foreach ($grid->allColumns() as $column) { ?>
					<tr>
						<td><input type="checkbox" name="cols[<?php echo $column ?>]" <?php echo checked($grid->columnVisible($column)) ?> /></td>
						<td><?php echo Schema::columnName($table, $column) ?></td>
					</tr>
					<?php } ?>
				</table>
				
				<input type="submit" value="Update" class="btnLink btnLinkLight icon iconReply" />
			</form>
		</div>
	</div>
	
	<div class="columnSort button">
		<a href="#" class="icon iconSort">Sorting</a>
	
		<div class="pod podOverlay">
			<div class="head">
				<h2 class="icon iconSort">Sorting</h2>
			</div>
			
			<form method="post" action="<?php echo routeUri(array("controller"=>"api", "action"=>"columnSort")) ?>" class="pad">
				<input type="hidden" name="direct" value="<?php echo Request::uriForDirect() ?>" />
				<input type="hidden" name="table" value="<?php echo $table ?>" />
				
				<table>
					<tr class="duplicate">
						<td>
							<?php echo select_box_tag("cols[{num}][sort]", $grid->columnOptions(), null, array(), array("disabled"=>"disabled")) ?>
						</td>
						<td>
							<?php echo select_box_tag("cols[{num}][dir]", sqlOrderOptions(), null, array(), array("disabled"=>"disabled")) ?>
						</td>
						<td>
							<a href="#" class="delRow">Del</a>
						</td>
					</tr>
					
					<?php
						$i = 0;
						foreach ($grid->sortColumns() as $column=>$dir) {
					?>
					<tr>
						<td>
							<?php 
							echo select_box_tag("cols[{$i}][sort]", $grid->columnOptions(), $column);
							?>
						</td>
						<td>
							<?php echo select_box_tag("cols[{$i}][dir]", sqlOrderOptions(), WoolAutoGrid::dirToSql($dir)) ?>
						</td>
						<td>
							<a href="#" class="delRow">Del</a>
						</td>
					</tr>
					<?php
							$i++;
						}
					?>
					
					<tr>
						<td colspan="3">
							<a href="#" class="btnLink btnLinkLight addRow">Add</a>
						</td>
					</tr>
				</table>
				
				<input type="submit" value="Update" class="btnLink btnLinkLight icon iconReply" />
			</form>
		</div>
	</div>
	
	<div class="button">
		<?php echo linkTo("Reset All", array("action"=>"reset", "table"=>$table), 'class="icon iconReset"') ?>
	</div>
	
	<div class="clear"></div>
</div>
