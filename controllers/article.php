<?php

require_once('Markdown/markdown.php');
require_once('Shaded/App/Articles/Article.php');

class ArticleController extends AppController {
	function startUp() {
		$this->addHelper('blog');
	}
	
	function index() {
		$this->location = str_replace($GLOBALS['BASE_URI'], '', Request::path());
		
		if (id_param('revision')) {
			$this->page = Article::revision($this->location, id_param('revision'))->fetchRow();
		} else {
			$this->page = Article::latest($this->location)->fetchRow();
		}
		
		if (!$this->page->articleId) {
			$this->render("404");
		}
	}
	
	public function history() {
		$this->location = param('location');
		$this->history = Article::historyOf($this->location)->rowSet();
	}
	
	function edit() {
		$this->location = param('location');
		
		if (id_param('revision')) {
			$this->page = Article::revision($this->location, id_param('revision'))->fetchRow();
		} else {
			$this->page = Article::latest($this->location)->fetchRow();
		}
		
		if (Request::isPost()) {
			if (Article::createRevision(param("page"))) {
				$this->redirectTo(baseUri($this->location));
			}
		}
	}
}
