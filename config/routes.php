<?php

Route::portal(array("default", "admin"), Route::PORTAL_LEADING_PATH);

// Wiki / articles
Route::add("@portal/wiki/edit", array("controller"=>"article", "action"=>"edit"));
Route::add("@portal/wiki/history", array("controller"=>"article", "action"=>"history"));

// Blog
Route::add("@portal/blog/:year/:month/:title", array("controller"=>"blog", "action"=>"view"));
Route::add("@portal/blog/:year/:month", array("controller"=>"blog", "action"=>"month"));
Route::add("@portal/blog/:year", array("controller"=>"blog", "action"=>"year"));

// Users
Route::add("@portal/user/:name", array("controller"=>"user", "action"=>"view"));

// Standard routes
Route::add("@portal/@controller/:id/@action");
Route::add("@portal/@controller/@action");
Route::add("@portal/@controller");

Route::add("", array("controller"=>"article", "action"=>"index"));
