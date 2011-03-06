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
}
