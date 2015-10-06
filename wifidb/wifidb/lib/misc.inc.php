<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/

class misc
{
    function __construct()
    {
        $this->ver_array['Misc'] = array(
                                    "getTZ"                 =>	"1.0",
                                    "tar_file"              =>	"1.1",
                                    "get_set"               =>	"1.0",
                                    "top_ssids"             =>  "1.1"
                                    );
    }

    #===============================================#
    #   Get the Name for a given Timezone number    #
    #===============================================#
    function getTZ($offset = '-5')
    {
            $timezones = array(
                            '-12'=>'Pacific/Kwajalein',
                            '-11'=>'Pacific/Samoa',
                            '-10'=>'Pacific/Honolulu',
                            '-9'=>'America/Juneau',
                            '-8'=>'America/Los_Angeles',
                            '-7'=>'America/Denver',
                            '-6'=>'America/Mexico_City',
                            '-5'=>'America/New_York',
                            '-4'=>'America/Caracas',
                            '-3.5'=>'America/St_Johns',
                            '-3'=>'America/Argentina/Buenos_Aires',
                            '-2'=>'Atlantic/Azores',// no cities here so just picking an hour ahead
                            '-1'=>'Atlantic/Azores',
                            '0'=>'Europe/London',
                            '1'=>'Europe/Paris',
                            '2'=>'Europe/Helsinki',
                            '3'=>'Europe/Moscow',
                            '3.5'=>'Asia/Tehran',
                            '4'=>'Asia/Baku',
                            '4.5'=>'Asia/Kabul',
                            '5'=>'Asia/Karachi',
                            '5.5'=>'Asia/Calcutta',
                            '6'=>'Asia/Colombo',
                            '7'=>'Asia/Bangkok',
                            '8'=>'Asia/Singapore',
                            '9'=>'Asia/Tokyo',
                            '9.5'=>'Australia/Darwin',
                            '10'=>'Pacific/Guam',
                            '11'=>'Asia/Magadan',
                            '12'=>'Asia/Kamchatka'
                            );
            return $timezones[$offset];
    }

    #======================#
    #   Tar a file up      #
    #======================#
    function tar_file($file)
    {
        $start = microtime(1);
        $filename_exp = explode( ".", $file);
        $filename_strip = $filename_exp[0];
        $archive = $this->TOOLS_PATH."/backups/".$filename_strip.".tar.gz";
        $script = "tar -czfv $archive $file";
        $results = system($script,$retval);
        if(!$results)
        {
            $stop = microtime(1);
            $time = ($stop-$start);
            $mbps = ((filesize($file)/1024)/1024)/$time;
            $return = array( $results, $retval, $time, $mbps, $archive );
            return $return;
        }else
        {
            return 0;
        }
    }

    #======================#
    #   Get Set values     #
    #======================#
    function get_set($table,$column)
    {
        $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
        if (!($ret = mysql_query($sql)))
        die("Error: Could not show columns");

        $line = mysql_fetch_assoc($ret);
        $set = $line['Type'];
        $set = substr($set,5,strlen($set)-7); // Remove "set(" at start and ");" at end
        return preg_split("/','/",$set); // Split into and array
    }

    #===================================================================#
    #   Calculate the number of times each unique ssid is in the DB     #
    #===================================================================#
    function top_ssids()
    {
        $ssids = array();
        $number = array();
        echo "Select from Pointers Table\r\n";
        $sql = "SELECT * FROM `wifi`.`wifi_pointers`";
        $result = $this->sql->conn->query($sql);
        $total_rows = $result->rowCount();
        if($total_rows != 0)
        {
            while ($files = $result->fetch_array())
            {
                $ssids[]    =   $files['ssid'];
            }
            echo "Gathered all SSIDs, now Uniqueifying it...\r\n";
            $ssids = array_unique($ssids);
            echo "Now sorting the array...\r\n";
            sort($ssids);
            echo "Find out the number of each SSID\r\n";
            foreach($ssids as $key=>$ssid)
            {
                $sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `ssid` LIKE '$ssid'";
                $result = $this->sql->conn->query($sql);
                $total_rows = $result->rowCount();
                $num_ssid[]	= array( 0=>$total_rows, 1=>$ssid);
            }
            echo "Sort again so the count is in decending order...\r\n";
            rsort($num_ssid);
            return $num_ssid;
        }else
        {
            echo "<h2>There is nothing to stat, import something so I can graph it.</h2>";
            return 0;
        }
    }
}
?>
