<?php
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api:locate");

include('../lib/init.inc.php');

$version   =   (@$_GET['version'] ? (int)$_GET['version'] : 0);
if($version){$dbcore->Output(array("WiFiDB API Start Date: $dbcore->startdate", "Last Edit: $dbcore->lastedit","Version Number: $dbcore->vernum", "Contact: $dbcore->contact"));}

$api = get_class_methods('api');
$core = get_class_methods('dbcore');
$API_Methods = array_merge(array_diff($api, $core), array_diff($core, $api));
$API_Details = array();
foreach($API_Methods as $method)
{
    $r = new ReflectionMethod('api', $method);
    $params = $r->getParameters();
    foreach ($params as $param) {
        //$param is an instance of ReflectionMethod
        $API_Details['API'][$method]['Method Name'] = $method;
        $API_Details['API'][$method]['params'][] = array('name'=>$param->getName(), 'optional'=>($param->isOptional() ? $param->isOptional() : 'false'));
    }
}
$dbcore->Output(array(
    "WiFiDB API Version"=>$dbcore->vernum,
    "WiFiDB API Author"=>$dbcore->Author,
    "WiFiDB API Functions"=>$API_Details
));
