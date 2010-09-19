<?php

require_once('Shaded/App/Articles/Article.php');

class Blog {
	public static function single() {
		return Query(<<<SQL
select r.*, u.*
from articles a
join article_revisions r on r.articleId = a.articleId
join users u on u.userId = r.authorId
where a.type = 'blog' and a.articleId = ? and r.publishedOn is not null
order by r.publishedOn desc
limit 1
SQL
		, 1);
	}
	
	public static function summaries() {
		return Query(<<<SQL
select r.*, u.*
from articles a
join article_revisions r on r.articleId = a.articleId
join users u on u.userId = r.authorId
where a.type = 'blog' and r.publishedOn is not null
order by a.createdOn desc
SQL
		);
	}
	
	public static function summariesBetween($start, $end) {
		return self::summaries()->andWhere("a.createdOn between ? and ?", array($start, $end));
	}
}
