<?php

require_once('Wool/App/Forum/Message.php');
require_once('Wool/Framework/WoolGrid.php');

class ForumController extends AppController {
	function startUp() {
		$this->addHelper('grid');
	}
	
	function index() {
		$this->threads = new WoolGrid("forum", ForumMessage::threadsFor("forum", 1));
		$this->threads->setPerPage(25);
		$this->replies = ForumMessage::messagesForThreads(pluck($this->threads, "id"))->rowSet();
	}
	
	function create() {
		$this->message = WoolTable::fetch("forum_message", null, "message");
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
		
		$this->message = WoolTable::fetch("forum_message", "id");
		
		if (Request::isPost()) {
			$this->reply = WoolTable::fetch("forum_message", null, "message");
			$this->reply->userId = Session::user()->userId;
			ForumMessage::replyTo($this->reply, $this->message);
		}
		
		$this->replies = ForumMessage::messagesForThreads(array($this->thread->id))->rowSet();
		
		if (Request::isAjax()) {
			$this->renderPartial('message');
		}
	}
	
	function delete() {
		$this->message = WoolTable::fetch("forum_message", "id");
		
		if (Request::isPost()) {
			$GLOBALS['MessageNestedSet']->removeNode($this->message->id);
			$this->redirectTo(array("action"=>"index"));
		}
		
		$this->replies = ForumMessage::messagesForThreads(array($this->message->threadId))->rowSet();
	}
}
