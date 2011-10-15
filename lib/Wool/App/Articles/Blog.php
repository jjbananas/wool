<?php

require_once('Wool/App/Articles/Article.php');

class Blog {
	public static function single($start, $end, $title) {
		return Query(<<<SQL
select a.location, r.*, u.*
from articles a
join article_revisions r on r.articleId = a.articleId
join user u on u.userId = r.authorId
where
	a.type = 'blog'
	and r.publishedOn is not null
	and a.location = ?
	and a.createdOn between ? and ?
order by r.publishedOn desc
limit 1
SQL
		, array($title, sqlDate($start), sqlDate($end)));
	}
	
	public static function summaries() {
		return Query(<<<SQL
select a.location, r.*, u.*
from articles a
join article_revisions r on r.articleId = a.articleId
join user u on u.userId = r.authorId
where a.type = 'blog' and r.publishedOn is not null
group by a.articleId
order by a.createdOn desc
SQL
		);
	}
	
	public static function summariesBetween($start, $end) {
		return self::summaries()->andWhere("a.createdOn between ? and ?", array($start, $end));
	}
}
