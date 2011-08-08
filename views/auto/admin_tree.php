<?php
	$self->js("/components/jsTree/jquery.jstree.js");
	$self->js("/js/auto/admin_edit.js");
	$self->js("/js/jquery.positionMatch.js");
	$self->css("/components/jsTree/themes/default/style.css");
?>
<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo Schema::displayName($table) ?></li>
	</ol>
	
	<div style="float: right;">
		<?php echo linkTo("Grid View", array("action"=>"table", "table"=>$table), 'class="btnLink icon iconTable"') ?>
		<?php echo linkTo("Tree View", array("action"=>"tree", "table"=>$table), 'class="btnLink icon iconTree"') ?>
	</div>
</div>

<div class="pad">
	<h1><?php echo Schema::displayName($table) ?></h1>
	<p><?php echo Schema::description($table) ?></p>
	
	<?php echo linkTo("New " . Schema::shortName($table), array("action"=>"edit", "table"=>$table), 'class="btnLink icon iconAddItem"') ?>
</div>

<div class="pad">
	<div class="pod">
		<div class="head">
			<h2 class="icon iconEdit"><?php echo Schema::displayName($table) ?> Tree View</h2>
		</div>
		
		<div class="body splitBody">
			<div class="s1of2" style="overflow: auto;">
				<div class="editPanel" id="jstree" data-move="<?php echo routeUri(array("controller"=>"api", "action"=>"moveNode")) ?>" data-delete="<?php echo routeUri(array("controller"=>"api", "action"=>"delete", "table"=>$table)) ?>">
					<ul>
						<?php $self->renderPartial("tree_branch", array("tree"=>$tree, "branch"=>$tree->byGroup("parentId", 0))) ?>
					</ul>
				</div>
			</div>
			
			<div class="s1of2">
				<div class="pad">
					<a href="#" class="btnLink icon iconEdit actionRoot">New Top-Level</a>
					<a href="#" class="btnLink icon iconEdit actionInsert">Insert Child</a>
					<a href="#" class="btnLink icon iconEdit actionRemove">Remove</a>
				</div>
			</div>
			
			<div class="clear"></div>
		</div>
	</div>
</div>

<div class="pad hide">
	<?php $self->renderPartial('table_edit') ?>
</div>

<?php $self->renderPartial('selection_grid') ?>
