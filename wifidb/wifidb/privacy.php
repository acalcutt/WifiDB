<?php
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

$dbcore->smarty->display('privacy.tpl');

?>