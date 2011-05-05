<?php foreach ($branch as $child) { ?>
<li>
	<a href="<?php echo $child->id ?>" data-node="<?php echo $child->id ?>"><?php echo $child->title ?></a>
	
	<?php if ($tree->byGroup("parentId", $child->id)) { ?>
	<ul>
		<?php $self->renderPartial("tree_branch", array("tree"=>$tree, "branch"=>$tree->byGroup("parentId", $child->id))) ?>
	</ul>
	<?php } ?>
</li>
<?php } ?>
