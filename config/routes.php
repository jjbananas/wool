<?php

Route::portal(array("default", "admin"), Route::PORTAL_LEADING_PATH);

// Wiki / articles
Route::add("@portal/wiki/edit", array("controller"=>"article", "action"=>"edit"));
Route::add("@portal/wiki/history", array("controller"=>"article", "action"=>"history"));
Route::add("@portal/wiki/delete", array("controller"=>"article", "action"=>"delete"));

// Blog
Route::add("@portal/blog/history/:id", array("controller"=>"blog", "action"=>"history"));
Route::add("@portal/blog/edit/:id", array("controller"=>"blog", "action"=>"edit"));
Route::add("@portal/blog/:year/:month/:title", array("controller"=>"blog", "action"=>"view"));
Route::add("@portal/blog/:year/:month", array("controller"=>"blog", "action"=>"month"));
Route::add("@portal/blog/:year", array("controller"=>"blog", "action"=>"year"));

// Users
Route::add("@portal/user/:id/@action", array("controller"=>"user"));
Route::add("@portal/user/@action", array("controller"=>"user"));
Route::add("@portal/user/:name", array("controller"=>"user", "action"=>"view"));

// Widgets
Route::add("@portal/widget/:page/:type/:area/:config", array("controller"=>"widget", "action"=>"index"));
Route::add("@portal/widget/:page/:type/:area", array("controller"=>"widget", "action"=>"index"));

// Auto-backend
Route::add("@portal/auto/:table/join/:id/:foreign", array("controller"=>"auto", "action"=>"join"));
Route::add("@portal/auto/:table/@action/:id", array("controller"=>"auto"));
Route::add("@portal/auto/:table/@action", array("controller"=>"auto"));
Route::add("@portal/auto/:table", array("controller"=>"auto", "action"=>"table"));
Route::add("@portal/api/@action", array("controller"=>"auto"));

// Standard routes
Route::add("@portal/@controller/:id/@action");
Route::add("@portal/@controller/@action");
Route::add("@portal/@controller");

Route::add("admin/:table", array("portal"=>"admin", "controller"=>"auto", "action"=>"table"));

Route::add("", array("controller"=>"page", "action"=>"index"));
