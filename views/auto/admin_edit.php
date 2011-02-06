<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo linkTo(WoolTable::displayName($table), array("action"=>"table", "table"=>$table)) ?></li>
		<li>Add/Edit <?php echo WoolTable::displayName($table) ?> #<?php $u = WoolTable::uniqueColumn($table); echo $item->$u ?></li>
	</ol>
</div>

<div class="pad">
	<form method="post">
		<div class="pod">
			<div class="head">
				<h2 class="icon iconEdit">Add/Edit <?php echo WoolTable::displayName($table) ?> #<?php $u = WoolTable::uniqueColumn($table); echo $item->$u ?></h2>
			</div>
			
			<div class="body splitBody">
				<div class="s1of2">
					<div class="editPanel">
						<?php foreach ($columns as $column=>$col) { ?>
						<?php if (WoolTable::columnIsKey($table, $column)) { ?>
						<div class="input foreign">
							<?php echo label($table, $column) ?>
							<?php echo hidden_field($item, "item", $column) ?>
							<a href="" class="choice icon iconKeyLink"><?php echo ($item->$column ? "{$item->$column}: {$item->title}" : 'None Selected') ?></a>
						</div>
						<?php } else { ?>
						<div class="input">
							<?php
								echo label($table, $column);
								echo auto_field($item, $table, $column);
							?>
						</div>
						<?php } ?>
						<?php } ?>
						
					</div>
				</div>
				
				<div class="s1of2">
					<div class="padh">
						<div class="pod podToolbar podEditTools">
							<div class="pad">
								<?php echo linkTo('Delete', array("action"=>"delete"), 'class="btnLink btnLinkLight icon iconDelete"') ?>
								<?php echo linkTo('History', array("action"=>"history"), 'class="btnLink btnLinkLight icon iconHistory "') ?>
							</div>
						</div>
						
						<div class="selectionGrid">
							<h3>Search: <span class="searchTarget">Product Id</span></h3>
							
							<div class="searchBar">
								<input type="text" name="search" value="Search or #ID" />
								<input type="image" src="<?php echo baseUri('/images/icons/search.png') ?>" value="Search" />
								<a href="#"><img src="<?php echo baseUri('/images/icons/close.png') ?>" alt="Close" title="Close" /></a>
							</div>
							
							<table>
								<tr>
									<td><span>1: Tony Marklove</span></td>
									<td class="select"><a href="#" class="icon iconAccept">Use</a></td>
								</tr>
								<tr>
									<td><span>1: Boris</span></td>
									<td class="select"><a href="#" class="icon iconAccept">Use</a></td>
								</tr>
								<tr>
									<td>
										<span>1: Billy Testerton</span>
										Optional supplimentary information.
									</td>
									<td class="select"><a href="#" class="icon iconAccept">Use</a></td>
								</tr>
							</table>
						</div>
					</div>
					
					<div class="pad derivedPanel">
						<?php foreach ($derivedColumns as $column=>$col) { ?>
						<div class="derived">
							<span class="column"><?php echo WoolTable::columnName($table, $column) ?></span>
							<span class="value"><?php echo $item->$column ?></span>
						</div>
						<?php } ?>
					</div>
				</div>
				
				<div class="clear"></div>
			</div>
			
			<div class="foot">
				<input type="submit" class="btnLink icon iconSave" value="Save" />
			</div>
		</div>
	</form>
</div>
