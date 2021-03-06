<?php

require(__DIR__ . "/../inc/global.php");
require(__DIR__ . "/../layout/graphs.php");
require_login();

$user = get_user(user_id());
require_user($user);

// adding a new page?
$title = require_post("title");

$title = substr($title, 0, 64); // limit to 64 characters
if (!$title) {
	$title = t("Untitled");
}

$errors = array();
$messages = array();
// check premium account limits
if (!can_user_add($user, 'graph_pages')) {
	$errors[] = t("Cannot add graph page: too many existing graph pages.") .
			($user['is_premium'] ? "" : " " . t("To add more graph pages, upgrade to a :premium_account.", array(':premium_account' => link_to(url_for('premium'), t('premium account')))));
	set_temporary_errors($errors);
	redirect(url_for('profile', array('page' => require_post("page", ""))));
}

// it's OK - let's add a new one
// first get the highest page order so far on this page
$q = db()->prepare("SELECT * FROM graph_pages WHERE user_id=? ORDER BY page_order DESC LIMIT 1");	// including is_removed (in case of restore)
$q->execute(array(user_id()));
$highest = $q->fetch();
$new_order = $highest ? ($highest['page_order'] + 1) : 1;

// now insert it
$q = db()->prepare("INSERT INTO graph_pages SET user_id=:user_id, title=:title, page_order=:page_order");
$q->execute(array(
	'user_id' => user_id(),
	'title' => $title,
	'page_order' => $new_order,
));
$new_page_id = db()->lastInsertId();
$messages[] = t("Added new graph page :title.", array(':title' => htmlspecialchars($title)));

// redirect
set_temporary_messages($messages);
redirect(url_for('profile', array('page' => $new_page_id)));
