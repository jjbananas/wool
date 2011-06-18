<div class="pod filter podToggleOpen <?php echo $data->isFiltering() ? 'active' : '' ?>">
	<div class="filterControls">
		<a href="<?php echo Request::uri(array("{$table}_clear"=>"true")) ?>" class="icon iconReset">Clear Search</a>
		<form>
			<div class="comboBox">
				<span class="btn btnLink btnLinkThin">Saved Searches</span>
				
				<div class="combo">
					<input type="text" />
					
					<ul class="options">
						<li>Tax free</li>
						<li>Some other</li>
						<li>BT Internet</li>
					</ul>
				</div>
			</div>
		</form>
		
		<div class="clear"></div>
	</div>
	
	<form class="filterOptions">
		Search: <?php echo text_field_tag("{$table}_filter", $data->filterParam(), array('class'=>"mainFilter")) ?>
		
		<div class="comboBox">
			<span class="btn btnLink btnLinkThin"><span class="label">Save Search</span></span>
			
			<div class="combo">
				<input type="text" name="<?php echo $table . "_save" ?>" data-action="" autocomplete="off" />
				
				<ul class="options">
					<li>Tax free</li>
					<li>Some other</li>
					<li>BT Internet</li>
				</ul>
				
				<ul class="fixed">
					<li><a href="">Manage Saved Searches</a></li>
				</ul>
			</div>
		</div>
		
		<div class="additionalOptions">
			<?php foreach (Schema::filterableColumns($table) as $colName=>$filterColumn) { ?>
			<div class="s1of3">
				<?php
					$self->renderPartial(
						(SqlTypes::isDate($filterColumn['type'])) ? "filter_date" : "filter_options",
						array("table"=>$table, "column"=>$colName, "data"=>$data)
					);
				?>
			</div>
			<?php } ?>
			
			<div class="save">
				<input type="submit" class="btnLink btnLinkLight btnLinkThin icon iconSearch" value="Search" />
			</div>
		</div>
	</form>
	
	<div class="foot">
	</div>
</div>
