<?php

class ArticleRevision {
	public static function define() {
		self::name("title", "Page Title");
		self::name("content", "Article Content");
		self::name("excerpt", "Excerpt");
	}
}
