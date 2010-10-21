<?php

require_once('Wool/App/Articles/Blog.php');
require_once('Wool/App/Forum/Message.php');

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
		$year = id_param('year', date('Y'));
		$month = id_param('month', date('m'));
		$title = param('title');
		
		$this->article = Blog::single(mkdate($month,1,$year), mkdate($month+1,1,$year), $title)->fetchRow();
		$this->forumLatest = ForumMessage::threadsFor("forum", 1)->limit(4)->rowSet();
	}
	
	function edit() {
		$article = WoolTable::fetch("articles", id_param('id'));
		$revision = WoolDb::fetchRow("select * from article_revisions where articleId = ?", $article->articleId);
		
		if (Request::isPost()) {
			$revision->articleRevisionId = null;
			
			WoolTable::fromArray($article, "article");
			WoolTable::fromArray($revision, "revision");
			
			if (!$article->location) {
				$article->location = str_replace(" ", "-", $article->title);
				$article->location = preg_replace("/[^\w]/", "", $article->location);
			}
			if (!$revision->excerpt) {
				$revision->excerpt = truncate($revision->content, 300);
			}
			
			$revision->authorId = Session::user()->userId;
			$revision->createdOn = now();
			$revision->publishedOn = now();
			
			$trans = new TransactionRaii;
			if (WoolTable::save($article) && WoolTable::save($revision)) {
				$trans->success();
				$this->redirectTo(array("controller"=>"blog", "action"=>"view"));
			}
		}
		
		$this->article = $article;
		$this->revision = $revision;
	}
}
