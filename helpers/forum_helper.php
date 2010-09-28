<?php

function message_active($message) {
	return $message->id == id_param('id');
}

function message_cls($message, $num, $total) {
	$cls = array();
	if ($message->threadId == $message->id) {
		$cls[] = 'root';
	}
	else if (message_active($message)) {
		$cls[] = 'active';
	}
	
	if ($num == $total-1) {
		$cls[] = 'last';
	}
	return join(' ', $cls);
}

function preview_cls($message, $messages) {
	$cls = array('msg');
	$num = 1;
	$messages = $messages->byGroup("threadId", $message->threadId);
	$count = count($messages);
	for ($num=1; $num<10 && $num<$count; $num++) {
		if ($message->id == $messages[$count-$num]->id) {
			$cls[] = 'latest' . $num;
			break;
		}
	}
	return join(' ', $cls);
}

function message_user_link($name) {
	return linkTo($name, array("controller"=>"user", "id"=>$name), 'class="user"');
}
