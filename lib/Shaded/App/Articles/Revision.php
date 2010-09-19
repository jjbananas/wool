<?php

class ArticleRevision extends EvanceTable {
	public static function define() {
		self::name("title", "Page Title");
		self::name("content", "Article Content");
	}
}

EvanceTable::registerTable("ArticleRevision", "article_revisions");
