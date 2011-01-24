<?php $self->addHelper('/std/navigation') ?>
<div class="dataGridNav">
	<div>
		<form action="<?php echo Request::uri() ?>" method="get">
			<?php echo toHiddenFormGet(array($pager->name() . "_page" => null, $pager->name() . "_perPage" => null)) ?>
			Page <input type="text" name="<?php echo $pager->name() ?>_page" value="<?php echo $pager->page() ?>" /> of <?php echo $pager->totalPages() ?>
			&nbsp;&nbsp;Show <select name="<?php echo $pager->name() ?>_perPage">
				<?php echo navPerPageOptions($pager) ?>
			</select>
			per page
			<input type="submit" style="display: none" />
		</form>
	</div>

	<ul>
		<li><?php echo navFirst($pager) ?></li>
		<li><?php echo navPrev($pager) ?></li>
		<li><?php echo navNext($pager) ?></li>
		<li><?php echo navLast($pager) ?></li>
	</ul>
</div>
