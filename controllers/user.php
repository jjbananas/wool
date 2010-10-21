<?php

require_once('Wool/Core/User.php');
require_once('Wool/App/Forum/Message.php');

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
		
		$this->user = WoolTable::fetch("users", null, "user");
		
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
			}
		}
	}
	
	function logout() {
		Session::logout();
		$this->redirectTo(baseUri('/'));
	}
}
