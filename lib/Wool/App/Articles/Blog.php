<?php

require_once('Wool/App/Articles/Article.php');

class Blog {
	public static function single($start, $end, $title) {
		return Query(<<<SQL
select b.*, u.name
from blog_post b
join user u on u.userId = b.userId
where
	b.publishedOn is not null
	and b.location = ?
	and b.createdOn between ? and ?
order by b.publishedOn desc
limit 1
SQL
		, array($title, sqlDate($start), sqlDate($end)));
	}
	
	public static function summaries($limit=null) {
		$limit = is_numeric($limit) ? "limit {$limit}" : "";

		return Query(<<<SQL
select b.*, u.name
from blog_post b
join user u on u.userId = b.userId
where b.publishedOn is not null
order by b.publishedOn desc
{$limit}
SQL
		);
	}
	
	public static function summariesBetween($start, $end) {
		return self::summaries()->andWhere("b.createdOn between ? and ?", array($start, $end));
	}
}
