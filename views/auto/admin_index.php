<div class="pad">
	<h1>Access Roles</h1>
	<p>Access roles allow you to control access to each page of your site.</p>
	
	<p><?php echo linkTo("New Role", array("action"=>"edit"), 'class="btnLink"') ?></p>
</div>

<div class="pad">
	<div class="pod">
		<table class="dataGrid" data-gridTable="<?php echo $table ?>" data-dragRows="true">
			<thead>
				<tr>
					<th></th>
					<?php foreach ($columns as $column) { ?>
					<th class="dragable" data-column="<?php echo $column ?>"><?php echo WoolTable::nameFor($table, $column) ?></th>
					<?php } ?>
					<th width="1"></th>
				</tr>
			</thead>
			
			<tbody>
				<?php foreach ($data as $item) { ?>
				<tr data-unique="<?php echo $item->userId ?>">
					<td class="rowSelect"><input type="checkbox" name="item[<?php echo $item->userId ?>]" /></td>
					<?php foreach ($columns as $column) { ?>
					<td><?php echo $item->$column ?></td>
					<?php } ?>
					<td><?php echo linkTo("Edit", array("action"=>"edit", "id"=>$item->userId), 'class="btnLink"') ?></td>
				</tr>
				<?php } ?>
			</tbody>
			
			<tfoot>
				<tr>
					<td colspan="0">
						<?php echo linkTo("New Role", array("action"=>"edit"), 'class="icon iconAddItem"') ?>
					</td>
				</tr>
				
				<tr style="display: none" data-action="<?php echo routeUri(array("controller"=>"auto", "action"=>"columnUpdate")) ?>">
					<td colspan="0">
						<label>Test</label>
						<input />
					</td>
				</tr>
			</tfoot>
		</table>
		
		<div class="foot">
			<div class="pod podNested pad">
				<?php $self->renderPartial('/grid/nav', array("pager"=>$data)) ?>
			</div>
		</div>
	</div>
</div>

<div class="pad">
	<div class="pod podOverlay" id="colunmEdit">
		<form action="<?php echo routeUri(array("controller"=>"auto", "action"=>"columnUpdate")) ?>" method="post">
			<div class="body editPanel">
				<label>Login URL</label>
				<input type="text" name="value" />
			</div>
			
			<div class="foot">
				<input type="submit" value="OK" class="btnLink" />
				<a href="#">Cancel</a>
			</div>
		</form>
	</div>
</div>