<?php

require_once('Shaded/App/Articles/Blog.php');
require_once('Shaded/App/Forum/Message.php');

class BlogController extends AppController {
	function startUp() {
		$this->addHelper('forum');
	}
	
	function index() {
		$this->articles = Blog::summaries()->rowSet();
		$this->forumLatest = ForumMessage::threadsFor("forum", 1)->limit(4)->rowSet();
	}
	
	function year() {
		$year = id_param('year', date('Y'));
		$this->articles = Blog::summariesBetween(mkdate(1,1,$year), mkdate(1,1,$year+1))->rowSet();
		$this->forumLatest = ForumMessage::threadsFor("forum", 1)->limit(4)->rowSet();
		
		$this->year = $year;
	}
	
	function month() {
		$year = id_param('year', date('Y'));
		$month = id_param('month', date('m'));
		
		$this->articles = Blog::summariesBetween(mkdate($month,1,$year), mkdate($month+1,1,$year))->rowSet();
		$this->forumLatest = ForumMessage::threadsFor("forum", 1)->limit(4)->rowSet();

		$this->year = $year;
		$this->month = $month;
	}
	
	function view() {
		$this->article = Blog::single(1)->fetchRow();
		$this->forumLatest = ForumMessage::threadsFor("forum", 1)->limit(4)->rowSet();
	}
}
