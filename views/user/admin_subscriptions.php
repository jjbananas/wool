<div class="pad">
	<ol class="breadcrumbs">
		<li><?php echo linkTo("Home", array("action"=>"index")) ?></li>
		<li><?php echo linkTo($user->name, array("action"=>"view", "id"=>$user->userId)) ?></li>
		<li>Edit Subscriptions</li>
	</ol>
</div>

<form method="post">
	<?php foreach ($subscriptions->byGroup("reference") as $group) { ?>
	<div class="pad">
		<div class="pod">
			<div class="head">
				<h2 class="icon <?php echo $group->value("customCampaign") ? 'iconSubscription' : 'iconNote' ?>"><?php echo $group->value("typeName") ?></h2>
			</div>

			<div class="body">
				<div class="editPanel">
					<table class="checkList">
						<?php foreach ($group as $sub) { ?>
						<tr>
							<td><?php echo check_box_tag("sub", $sub->messageTemplateId, isSubscribed($current, $sub)) ?></td>
							<td><?php echo $sub->name ?> (<?php echo $sub->sendTarget?>)</td>
						</tr>
						<?php } ?>
					</table>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>

	<div class="pad">
		<input type="submit" class="btnLink icon iconSave" value="Save" />
	</div>
</form>