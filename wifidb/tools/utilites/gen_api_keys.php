<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

$parm = parseArgs($argv);
$all_users = @$parm['all_users'];
$user = @$parm['user'];
unset($parm);
$conn = prep();
if($all_users)
{
    echo "Going to clear out the GPS data from the pointers table.\r\n";
    $sql = "SELECT id,username FROM `wifi`.`user_info`";
    $result = $conn->query($sql);
    while($users_array = $result->fetch_array(1))
    {
        echo "------------------------------------\r\n";
        echo "Username: ".$users_array['username']."\r\n";
        echo "ID: ".$users_array['id']."\r\n";
        $id = $users_array['id'];
        $key = gen_key();
        echo "API Key: ".$key."\r\n";

        $update = "UPDATE `wifi`.`user_info` SET `apikey` = '$key' WHERE `id` = '$id'";
        if($conn->query($update))
        {
            echo "Updated users APIkey.\r\n";$good++;
        }else
        {
            echo "Failed to update user's APIkey :(\r\n";$bad++;
        }
    }
    echo "------------------------------------\r\n";
}elseif($user)
{
    $sql = "SELECT id,username FROM `wifi`.`user_info` WHERE `username` = '$user'";
    $result = $conn->query($sql);
    $users_array = $result->fetch_array(1);
    echo "Username: ".$user."\r\n";
    echo "ID: ".$users_array['id']."\r\n";
    $id = $users_array['id'];
    $key = gen_key();
    echo "API Key: ".$key."\r\n";
    $update = "UPDATE `wifi`.`user_info` SET `apikey` = '$key' WHERE `id` = '$id'";
    if($conn->query($update))
    {
        echo "Updated users APIkey.\r\n";
    }else
    {
        echo "Failed to update user's APIkey :(\r\n";
    }

}else{
    die("You need to pass an argument for this script to run.\r\n--user=%username% or --all_users\r\n\r\n");
}




function gen_key()
{
	$base           =   'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789!@+-=';
	$max            =   strlen($base)-1;
	$seed_len_gen   =   42;
	$key            =   '';
	mt_srand((double)microtime()*1000000);
	while(strlen($key) < $seed_len_gen+1)
	{$key.=$base{mt_rand(0,$max)};}
	return $key;
}
function prep()
{
    if(!(@require_once 'daemon/config.inc.php')){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
    if($GLOBALS['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
    require_once $GLOBALS['wifidb_install']."lib/config.inc.php";
    $conn =   new mysqli($host, $db_user, $db_pwd);
    return $conn;
}

function parseArgs($argv){
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg)
    {
        if (substr($arg,0,2) == '--'){
            $eqPos = strpos($arg,'=');
            if ($eqPos === false){
                $key = substr($arg,2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key = substr($arg,2,$eqPos-2);
                $out[$key] = substr($arg,$eqPos+1);
            }
        } else if (substr($arg,0,1) == '-'){
            if (substr($arg,2,1) == '='){
                $key = substr($arg,1,1);
                $out[$key] = substr($arg,3);
            } else {
                $chars = str_split(substr($arg,1));
                foreach ($chars as $char){
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }
    return $out;
}
?>