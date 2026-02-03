<?php
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

// Load privacy content from central location
$privacy_content_file = __DIR__ . '/content/privacy_content.html';
$privacy_content = file_exists($privacy_content_file) ? file_get_contents($privacy_content_file) : '<p>Privacy Policy content not found.</p>';

$dbcore->smarty->assign('wifidb_page_label', 'Privacy Policy');
$dbcore->smarty->assign('policy_content', $privacy_content);
$dbcore->smarty->display('privacy.tpl');
?>