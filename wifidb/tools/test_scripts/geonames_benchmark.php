<?php
global $switches;
$switches = array('extras'=>'api','screen'=>"CLI");

require( '../daemon/config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$gps = array(
    array(-17.64628336, 80.37464522),
    array(47.47325227, 173.31837626),
    array(-6.40239624, 92.17759255),
    array(27.71150116, 41.9388034),
    array(34.22674619, 62.38817042),
    array(38.35454343, 106.69732784),
    array(51.188339, 63.63293564),
    array(10.98001213, -159.54368842),
    array(21.88336717, 72.96014794),
    array(41.20460277, 116.68822346),
    array(32.15488722, 71.5826349),
    array(70.36842883, -128.24529256),
    array(72.1093548, -78.70169539),
    array(54.47404076, -177.78996953),
    array(-4.35297981, 174.13735663),
    array(-2.06752594, 122.41870146),
    array(-7.73511884, 110.26665261),
    array(59.09561156, -130.14925394),
    array(-21.61166573, 92.59299286),
    array(2.93928634, 168.72264761),
    array(19.30779213, 149.72420955),
    array(62.95723752, 103.13603913)
);
$k = 0;
for ($i=0; $i < 21; $i++)
{
    $pid = pcntl_fork();
    
    echo "Starting Benchmark $i:\r\n";
    if($gps[$k][0] == "")
    {
        pcntl_wait($status);
    }
    $dbcore->GeoNames($gps[$k][0], $gps[$k][1]);
    $dbcore->mesg['benchmark_id'] = $i;
    $dbcore->Output();
    $k++;
}
?>
