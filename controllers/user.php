<?php

require_once('Wool/Core/User.php');
require_once('Wool/App/Forum/Message.php');
require_once('Wool/App/Message/Message.php');

class UserController extends AppController {
	function index() {
		$this->user = Session::user();
		$this->threads = ForumMessage::messagesForUser($this->user->userId)->limit(25)->rowSet();
	}
	
	function view() {
		$this->user = User::profileByName(param('name'))->fetchRow();
		$this->threads = ForumMessage::messagesForUser($this->user->userId)->limit(25)->rowSet();
	}
	
	function signup() {
		if (Session::loggedIn()) {
			$this->redirectTo(array("action"=>"index"));
		}
		
		$this->user = WoolTable::fetch("user", null, "user");
		
		if (Request::isPost()) {
			if (WoolTable::save($this->user)) {
				Session::loginUser($this->user->userId);
				$this->redirectTo(array("action"=>"index"));
			}
		}
	}
	
	function login() {
		if (Request::isPost()) {
			if (Session::login(param('user'), param('pass'))) {
				$this->redirectTo(array("action"=>"index"));
			} else {
				WoolErrors::add($this, "user", "Your user name or password is incorrect. Please try again.");
			}
		}
	}
	
	function logout() {
		Session::logout();
		$this->redirectTo(baseUri('/'));
	}
	
	
	function adminLogin() {
		$this->login();
		$this->render('login');
	}
	
	function adminLogout() {
		Session::logout();
		$this->redirectTo(array("action"=>"login"));
	}
	
	function adminDenied() {
	}
	
	function adminView() {
		$this->user = User::profileByName(urldecode(param('name')))->fetchRow();
	}

	function adminSubscriptions() {
		$this->user = WoolTable::fetch("user", "id");
		$this->subscriptions = WoolMessage::subscribable()->rowSet();

		$this->current = new RowSet("select * from message_template_user where userId = ?", $this->user->userId);

		if (Request::isPost()) {
			$save = array();

			foreach ($this->subscriptions as $sub) {
				if (param(array("sub", $sub->messageTemplateId))) {
					$cur = $this->current->by("messageTemplateId", $sub->messageTemplateId);

					if (!$cur) {
						$cur = WoolTable::blank("message_template_user");
						$cur->messageTemplateId = $sub->messageTemplateId;
						$cur->userId = $this->user->userId;
						$cur->unsubscribed = false;
					} else {
						$cur->unsubscribed = false;
					}

					$save[] = $cur;
				} else {
					$cur = $this->current->by("messageTemplateId", $sub->messageTemplateId);
					if ($cur) {
						$cur->unsubscribed = true;
						$save[] = $cur;
					}
				}
			}

			if (WoolTable::save($save)) {
				$this->redirectTo(array("action"=>"view", "id"=>$this->user->userId));
			}
		}
	}
}
