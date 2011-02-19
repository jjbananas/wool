<?php $self->addHelper('/std/navigation') ?>
<div class="dataGridNav">
	<div>
		<form action="<?php echo Request::uri() ?>" method="get">
			<?php echo toHiddenFormGet(array($pager->name() . "_page" => null, $pager->name() . "_perPage" => null)) ?>
			Page <input type="text" name="<?php echo $pager->name() ?>_page" value="<?php echo $pager->page() ?>" size="2" /> of <?php echo $pager->totalPages() ?>
			&nbsp;&nbsp;Show <input type="text" name="<?php echo $pager->name() ?>_perPage" value="10" size="2" />
			per page
			<input type="submit" style="display: none" />
		</form>
	</div>

	<ul style="text-align: right;">
		<li><?php echo navFirst($pager, 'class="btnLink icon iconFirst"') ?></li>
		<li><?php echo navPrev($pager, 'class="btnLink icon iconPrev"') ?></li>
		<?php foreach (navPageLinks($pager, 9) as $page) { ?>
		<li class="<?php $page == $pager->page() ? 'active' : '' ?>"><?php echo navPageLink($pager, $page, 'class="btnLink"') ?></li>
		<?php } ?>
		<li><?php echo navNext($pager, 'class="btnLink icon iconNext"') ?></li>
		<li><?php echo navLast($pager, 'class="btnLink icon iconLast"') ?></li>
	</ul>
	
	<br class="clear" />
</div>
