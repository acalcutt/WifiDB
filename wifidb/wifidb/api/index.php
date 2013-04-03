<?php
global $switches;
$switches = array('screen'=>"HTML",'extras'=>'API');

$version   =   (@$_GET['version'] ? filter_input(INPUT_GET, 'version', FILTER_SANITIZE_ENCODED, array(16,32) ) : 0);

include('../lib/init.inc.php');

if($version){$dbcore->Output("WiFiDB API</br>\r\nStart Date: $dbcore->startdate</br>\r\nLast Edit: $dbcore->lastedit</br>\r\nVersion Number: $dbcore->vernum</br>\r\nContact: $dbcore->contact</br>\r\n");}
$api = get_class_methods('api');
$core = get_class_methods('dbcore');
$API_Methods = array_merge(array_diff($api, $core), array_diff($core, $api));
$API_Details = array();
foreach($API_Methods as $method)
{
    $r = new ReflectionMethod('api', $method);
    $params = $r->getParameters();
    foreach ($params as $param) {
        //$param is an instance of ReflectionParameter
        $API_Details['API'][$method]['Method Name'] = $method;
        $API_Details['API'][$method]['params'][] = array('name'=>$param->getName(), 'optional'=>($param->isOptional() ? $param->isOptional() : '0'));
    }
}
$dbcore->Output(array(
    "WiFiDB API Version"=>$dbcore->vernum,
    "WiFiDB API Author"=>$dbcore->Author,
    "WiFiDB API Functions"=>$API_Details
))

?>