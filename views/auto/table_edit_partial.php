<form method="post" class="tableEdit">	<div class="pod">		<div class="head">			<h2 class="icon iconEdit">Add/Edit <?php echo Schema::displayName($table) ?> #<?php $u = Schema::uniqueColumn($table); echo $item->$u ?></h2>		</div>				<div class="body splitBody">			<div class="s1of2">				<div class="editPanel">					<?php echo renderNotices() ?>										<?php foreach ($columns as $column=>$col) { ?>					<?php if ($ref = Schema::columnIsKey($table, $column)) { ?>					<div class="input foreign" data-references="<?php echo $ref ?>">						<?php echo label($item, "item", $column) ?>						<?php echo hidden_field($item, "item", $column) ?>						<a href="" class="choice icon iconKeyLink"><?php echo keyColumnDisplay($item, $column, $ref) ?></a>					</div>					<?php } else { ?>					<div class="input">						<?php							echo label($item, "item", $column);							echo auto_field($item, $table, $column);						?>					</div>					<?php } ?>					<?php } ?>				</div>			</div>						<div class="s1of2">				<div class="padh">					<?php if ($item->$u) { ?>					<div class="pod podToolbar podEditTools">						<div class="pad">							<?php echo linkTo('Delete', array("action"=>"delete", "table"=>$table, "id"=>$item->$u), 'class="btnLink btnLinkLight icon iconDelete"') ?>							<?php if (Schema::tableHasHistory($table)) { ?>							<?php echo linkTo('History', array("action"=>"history", "table"=>$table, "id"=>$item->$u), 'class="btnLink btnLinkLight icon iconHistory "') ?>							<?php } ?>						</div>					</div>					<?php } ?>										<div class="selectionGridSpacer">					</div>				</div>								<div class="pad derivedPanel">					<?php foreach ($derivedColumns as $column=>$col) { ?>					<div class="derived">						<span class="column"><?php echo Schema::columnName($table, $column) ?></span>						<span class="value"><?php echo $item->$column ?></span>					</div>					<?php } ?>				</div>			</div>						<div class="clear"></div>		</div>				<div class="foot">			<input type="submit" class="btnLink icon iconSave" value="Save" />		</div>	</div></form>