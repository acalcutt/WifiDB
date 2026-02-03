<?php
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

// Load cookie policy content from central location
$content_file = __DIR__ . '/content/cookies_content.html';
$policy_content = file_exists($content_file) ? file_get_contents($content_file) : '<p>Cookie Policy content not found.</p>';

$dbcore->smarty->assign('wifidb_page_label', 'Cookie Policy');
$dbcore->smarty->assign('policy_content', $policy_content);
$dbcore->smarty->display('cookies.tpl');
?>
