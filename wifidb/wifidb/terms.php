<?php
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

// Load terms content from central location
$terms_content_file = __DIR__ . '/content/terms_content.html';
$terms_content = file_exists($terms_content_file) ? file_get_contents($terms_content_file) : '<p>Terms of Use content not found.</p>';

$dbcore->smarty->assign('wifidb_page_label', 'User Agreement');
$dbcore->smarty->assign('policy_content', $terms_content);
$dbcore->smarty->display('terms.tpl');
?>
