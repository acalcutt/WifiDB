<?php
/*
notifications.php, the member's in-app notifications (an import finished, a failure, a new member for administrators)
and which kinds they want. Changes are made with POST; the cookie is SameSite=Lax, so another site cannot send one.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.
*/

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

include('../lib/init.inc.php');
require_once dirname(__FILE__).'/../lib/notify.inc.php';

$dbcore->smarty->assign('wifidb_page_label', 'Notifications');
$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
$username = $dbcore->sec->LoginUser;

if(!$username)
{
	$dbcore->redirect_page("/wifidb/login.php?return=".urlencode("/wifidb/cp/".basename($_SERVER['REQUEST_URI'])), 1000);
	$dbcore->smarty->assign('user_cp_profile', array('message' => "<br>You must be logged in to see your notifications. Redirecting to login page.<br><br>"));
	$dbcore->smarty->display('user_cp_msg.tpl');
	exit;
}


# Choosing which notifications to get is a preference: it asks for the second factor if the member chose that.
if($func === 'settings') { $dbcore->sec->RequirePreferencesConfirmation('cp/notifications.php?func=settings'); }

$notifier = new Notifier($dbcore->sql->conn);
$post = ($_SERVER['REQUEST_METHOD'] === 'POST');
$page = max(1, (int)filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT));
$perPage = 25;

try
{
	switch($func)
	{
		case 'markread':
			if($post) { $notifier->MarkRead($username, (int)@$_POST['id']); }
			header('Location: notifications.php?page='.$page);
			exit;

		case 'markall':
			if($post) { $notifier->MarkAllRead($username); }
			header('Location: notifications.php');
			exit;

		case 'delete':
			if($post) { $notifier->Delete($username, (int)@$_POST['id']); }
			header('Location: notifications.php?page='.$page);
			exit;

		case 'settings':
			$saved = false;
			if($post)
			{
				foreach($notifier->Prefs($username) as $type => $info)
				{
					$notifier->SetPref($username, $type, isset($_POST['type'][$type]));
				}
				$saved = true;
			}
			$dbcore->smarty->assign('wifidb_page_label', 'Notification Settings');
			$dbcore->smarty->assign('notification_prefs', $notifier->Prefs($username));
			$dbcore->smarty->assign('notification_saved', $saved);
			$dbcore->smarty->display('user_cp_notification_settings.tpl');
			break;

		default:
			$total = $notifier->Total($username);
			$list = array();
			foreach($notifier->Inbox($username, $perPage, ($page - 1) * $perPage) as $row)
			{
				// the template escapes everything it prints; the link has already been limited to a site or web address
				$row['unread'] = ($row['read_at'] === null);
				$list[] = $row;
			}
			$dbcore->smarty->assign('notifications', $list);
			$dbcore->smarty->assign('notification_total', $total);
			$dbcore->smarty->assign('notification_unread', $notifier->UnreadCount($username));
			$dbcore->smarty->assign('notification_page', $page);
			$dbcore->smarty->assign('notification_pages', max(1, (int)ceil($total / $perPage)));
			$dbcore->smarty->display('user_cp_notifications.tpl');
			break;
	}
}
catch(Throwable $e)
{
	// most likely the migration that creates the tables has not been run (tools/utilites/migrate.php)
	$dbcore->smarty->assign('user_cp_profile', array('message' => "<br>Notifications are not available yet.<br><br>"));
	$dbcore->smarty->display('user_cp_msg.tpl');
}
