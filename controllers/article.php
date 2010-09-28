<?php

require_once('Markdown/markdown.php');
require_once('Text/Diff.php');
require_once('Text/Diff/Renderer.php');
require_once('Text/Diff/Renderer/inline.php');
require_once('Text/Diff/Renderer/context.php');
require_once('Text/Diff/Renderer/unified.php');
require_once('Shaded/App/Articles/Article.php');

class ArticleController extends AppController {
	function startUp() {
		$this->addHelper('blog');
	}
	
	function index() {
		$this->location = Request::path(true);
		
		if (id_param('revision')) {
			$this->page = Article::revision($this->location, id_param('revision'))->fetchRow();
		} else {
			$this->page = Article::latest($this->location)->fetchRow();
		}
		
		if (!$this->page->articleId) {
			$this->render("404");
		}
	}
	
	public function diff() {
		$one = explode("\n", WoolDb::fetchRow("select * from article_revisions where articleRevisionId = 8")->content);
		$two = explode("\n", WoolDb::fetchRow("select * from article_revisions where articleRevisionId = 9")->content);
		
		$diff = new Text_Diff('auto', array($one, $two));

		/* Output the diff in unified format. */
		$renderer = new Text_Diff_Renderer_unified();
		echo $renderer->render($diff);
		$diff = new Text_Diff('auto', array($two, $one));

		/* Output the diff in unified format. */
		$renderer = new Text_Diff_Renderer_unified();
		echo $renderer->render($diff);
		exit;
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
			if (Article::createRevision($this->location, param("page"))) {
				$this->redirectTo(baseUri($this->location));
			}
		}
	}
	
	function delete() {
		$this->page = Article::latest(param('location'))->fetchRow();
		
		if (Request::isPost()) {
			if (Article::delete(param('location'))) {
				$this->redirectTo(baseUri('/'));
			}
		}
	}
}
