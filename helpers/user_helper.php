<?php

function isSubscribed($cur, $item) {
	$value = $cur->by("messageTemplateId", $item->messageTemplateId);

	return ($value && !$value->unsubscribed) ? $item->messageTemplateId : false;
}