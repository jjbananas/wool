<?php

require_once('Shaded/App/Articles/Revision.php');

class Article extends WoolTable {
	public static function define() {
		self::defaultValue("createdOn", now());
	}
	
	public static function preValidate($article) {
		if (sqlEmptyDate($article->createdOn)) {
			$article->createdOn = now();
		}
	}
	
	public static function latest($location) {
		return Query(<<<SQL
select a.location, r.*, u.*
from articles a
join article_revisions r on r.articleId = a.articleId
join users u on u.userId = r.authorId
where a.location = ? and a.deleted = 'N'
order by r.publishedOn desc
limit 1
SQL
		, $location);
	}
	
	public static function revision($location, $revision) {
		return self::latest($location)->andWhere("r.articleRevisionId = ?", $revision);
	}
	
	public static function historyOf($location) {
		return Query(<<<SQL
select r.*, u.*
from articles a
join article_revisions r on r.articleId = a.articleId
join users u on u.userId = r.authorId
where a.location = ?
order by r.publishedOn desc
SQL
		, $location);
	}
	
	public static function editable($location) {
		return Query(<<<SQL
select a.*
from articles a
where a.location = ?
limit 1
SQL
		, $location);
	}
	
	public static function createRevision($location, $post) {
		$article = self::editable($location)->fetchRow();
		$revision = WoolTable::blank("article_revisions");
		
		$article->location = $location;
		$article->deleted = 'N';
		
		WoolTable::fromArray($article, $post);
		WoolTable::fromArray($revision, $post);
		
		$trans = new TransactionRaii;
		if (!$article->articleId) {
			if (!WoolTable::save($article)) {
				return false;
			}
		}
		
		$revision->articleId = $article->articleId;
		$revision->articleRevisionId = null;
		$revision->authorId = Session::user()->userId;
		$revision->createdOn = now();
		$revision->publishedOn = now();
		
		if (!WoolTable::save($revision)) {
			return false;
		}
		
		$trans->success();
		return true;
	}
	
	public static function delete($location) {
		return self::createRevision($location, array(
			"deleted" => 'Y',
			"title" => "Deleted",
			"content" => "Deleted"
		));
	}
}

WoolTable::registerTable("Article", "articles");
