<?php

class ArticleRevision extends WoolTable {
	public static function define() {
		self::name("title", "Page Title");
		self::name("content", "Article Content");
	}
}

WoolTable::registerTable("ArticleRevision", "article_revisions");
