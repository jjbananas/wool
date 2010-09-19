<?php

require_once('Shaded/App/Forum/Message.php');
require_once('Shaded/Framework/EvanceGrid.php');

class ForumController extends AppController {
	function startUp() {
		$this->addHelper('grid');
	}
	
	function index() {
		$this->threads = new EvanceGrid("forum", ForumMessage::threadsFor("forum", 1));
		$this->threads->setPerPage(25);
		$this->replies = ForumMessage::messagesForThreads(pluck($this->threads, "id"))->rowSet();
	}
	
	function create() {
		$this->message = EvanceTable::fetch("forum_messages", null, "message");
		$this->message->userId = Session::user()->userId;
		
		if (Request::isPost()) {
			if (ForumMessage::attachTo($this->message, "forum", 1)) {
				$this->redirectTo(array("action"=>"index"));
			}
		}
	}
	
	function message() {
		$this->thread = ForumMessage::rootThreadOf(id_param('id'))->fetchRow();
		$this->thread_num = 1;
		$this->thread_total = 1;
		
		$this->message = EvanceTable::fetch("forum_messages", "id");
		
		if (Request::isPost()) {
			$this->reply = EvanceTable::fetch("forum_messages", null, "message");
			$this->reply->userId = Session::user()->userId;
			ForumMessage::replyTo($this->reply, $this->message);
		}
		
		$this->replies = ForumMessage::messagesForThreads(array($this->thread->id))->rowSet();
		
		if (Request::isAjax()) {
			$this->renderPartial('message');
		}
	}
	
	function delete() {
		$this->message = EvanceTable::fetch("forum_messages", "id");
		
		if (Request::isPost()) {
			$GLOBALS['MessageNestedSet']->removeNode($this->message->id);
			$this->redirectTo(array("action"=>"index"));
		}
		
		$this->replies = ForumMessage::messagesForThreads(array($this->message->threadId))->rowSet();
	}
}
