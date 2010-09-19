<?php

require_once('Shaded/Core/User.php');

class UserController extends AppController {
	function index() {
		$this->user = Session::user();
	}
	
	function view() {
		$this->user = User::profileByName(param('name'))->fetchRow();
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
