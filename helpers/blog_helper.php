<?php

function published_date($blog) {
	return date('jS M Y', strtotime($blog->publishedOn));
}

function excerpt($blog) {
	return $blog->excerpt ? $blog->excerpt : truncate($blog->content, 200);
}

function blogYearLink($blog) {
	$date = strtotime($blog->createdOn);
	return linkTo(date('Y', $date), array(
		"controller"=>"blog",
		"action"=>"index",
		"year"=>date('Y', $date)
	));
}

function blogMonthLink($blog) {
	$date = strtotime($blog->createdOn);
	return linkTo(date('F', $date), array(
		"controller"=>"blog",
		"action"=>"index",
		"year"=>date('Y', $date),
		"month"=>date('m', $date)
	));
}

function blogUri($blog) {
	$date = strtotime($blog->createdOn);
	return routeUri(array("controller"=>"blog", "action"=>"view", "year"=>date('Y',$date), "month"=>date('m',$date), "title"=>$blog->location));
}
