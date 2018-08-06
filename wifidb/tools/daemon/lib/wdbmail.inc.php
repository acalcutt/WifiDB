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


class wdbmail
{
    function __construct($dbcore)
    {
        require_once('MAIL5.php');
        $this->mail     =   new MAIL5();
        $this->SMTP     =   $dbcore->smtp;
        $this->WDBadmin =   $dbcore->WDBadmin;
    }
    
    function mail_password_reset($username = "", $Useremail = 'noone@somewhere.local')
    {
        
        ##########################
        $validatecode = $this->gen_keys(12);
        ##########################
        if(!$this->mail->from($this->wdbadmin))
        {die("Failed to add From address\r\n");}
        if(!$this->mail->addto($Useremail))
        {die("Failed to add To address\r\n");}

        if(!$this->mail->subject("WiFiDB User Password Reset"))
        {die("Failed to add subject\r\n");}

        $contents = "
You have requested a reset of your password, here it is...
Your account: $username
Temp Password: $validatecode

Go here to reset it to one you choose:
".$GLOBALS['UPATH']."/login.php?func=reset_password

-WiFiDB Service";

        if(!$this->mail->text($contents))
        {die("Failed to add message\r\n");}

        $smtp_conn = $this->mail->connect($this->smtp, 465, $sender, $sender_pass, 'tls', 10);
        if ($smtp_conn)
        {
            if($this->mail->send($smtp_conn))
            {
                $password = md5($validatecode.$seed);
                $update = "UPDATE `wifi`.`user_info` SET `password` = '$password' WHERE `username` = '$username'";
        #	echo $update."<BR>";
                if($this->sql->conn->query($update))
                {
                    echo "<font color='green'><h2>Password reset email sent.</h2></font>";
                }else
                {
                    echo "Mysql Error: ".var_export($this->sql->conn->errorInfo(), 1);
                }
            }
            else
            {
                echo "<font color='red'><h2>Password reset email Failed to send.</h2></font>";
            }
        }
        else
        {
            echo "<font color='red'><h2>Failed to connect to SMTP Host.</h2></font>";
        }
        $this->mail->disconnect();
    }
    #===============================================#
    #   Filtering of Mail to User privlidge type    #
    #===============================================#
    function sql_type_mail_filter($type = 'none')
    {
        switch($type)
        {
            case "schedule":
                $sql = " AND `schedule` = '1'";
            break;

            case "import":
                $sql = " AND `imports` = '1'";
            break;

            case "kmz":
                $sql = " AND `kmz` = '1'";
            break;

            case "new_users":
                $sql = "AND `new_users` = '1'";
            break;

            case "statistics":
                $sql = " AND `statistics` = '1'";
            break;

            case "perfmon":
                $sql = " AND `perfmon` = '1'";
            break;

            case "announcements":
                $sql = " AND `announcements` = '1'";
            break;

            case "announce_comment":
                $sql = " AND `announce_comment` = '1'";
            break;

            case "pub_geocache":
                $sql = " AND `pub_geocache` = '1'";
            break;

            case "geonamed":
                $sql = " AND `geonamed` = '1'";
            break;

            case "none":
                $sql = '';
            break;

            default:
                $sql = '';
            break;
        }
        return $sql;
    }

    #===========================================================#
    #   mail_users (Emails the admins of the DB about updates)  #
    #===========================================================#
    function mail_users($contents = '', $subject = "WifiDB Notifications", $type = "none", $error_f = 0)
    {
        if($type != 'none' or $type != '')
        {
        #	echo $GLOBALS['wifidb_email_updates'];
            if($this->wifidb_email_updates)
            {
                $db			= 	$GLOBALS['db'];
                $user_logins_table	=	$GLOBALS['user_logins_table'];
                $from			=	$GLOBALS['admin_email'];
                $wifidb_smtp		=	$this->smtp;
                $sender			=	$from;
                $sender_pass		=	$GLOBALS['wifidb_from_pass'];
                $to			=	array();
                $sql			=	"SELECT `email`, `username` FROM `wifi`.`$user_logins_table` WHERE `disabled` = '0' AND `validated` = '0'";
    #		echo $sql."<BR>";
                $sql .= sql_type_mail_filter($type);
                if($error_f)
                {
                    $sql .= " AND `admins` = '1'";
                }
    #		echo $sql."<BR>";
                if(!$error_f){$sql .= " AND `username` NOT LIKE 'admin%'";}
    #		echo $sql."<BR>";
                $result = $this->sql->conn->query($sql);
                while($users = mysql_fetch_array($result))
                {
        #           echo "To: ".$users['email']."\r\n";
                    if($this->mail->addbcc($users['email']))
                    {continue;}else{die("Failed to add BCC".$users['email']."\r\n");}
                }

                if(!$this->mail->from($from))
                {die("Failed to add From address\r\n");}
                if(!$this->mail->addto($from))
                {die("Failed to add Initial To address\r\n");}

                if($error_f){$subject .= " ^*^*^*^ ERROR! ^*^*^*^";}
    #	echo "subject: ".$subject."\r\n";
                if(!$this->mail->subject($subject))
                {die("Failed to add subject\r\n");}

    #	echo "Contents: ".$contents."\r\n";
                if(!$this->mail->text($contents))
                {die("Failed to add message\r\n");}

    #	echo "Trying to connect....\r\n";
                $smtp_conn = $this->mail->connect($wifidb_smtp, 465, $sender, $sender_pass, 'tls', 10);
                if ($smtp_conn)
                {
    #	echo "Successfully connected !\r\n";
                    $smtp_send = $this->mail->send($smtp_conn);
                    if($smtp_send)
                    {
                    #	echo "Sent!\r\n";
                        return 1;
                    }
                    else
                    {
                    #	print_r($_RESULT);
                        return 0;
                    }
                }else
                {
                #	print_r($_RESULT);
                    return 0;
                }
                $this->mail->disconnect();
            }else
            {
                #echo $GLOBALS['wifidb_email_updates'];
                #echo "Mail updates is turned off.";
                return 1;
            }
        }else
        {
            echo "$"."type var is not set, check your code.<br>\r\n";
            return 0;
        }
    }

    #===================================#
    #   Email for user verification     #
    #===================================#
    function mail_validation($to = '', $username = '')
    {
        require_once('config.inc.php');
        require_once('security.inc.php');
        require_once('MAIL5.php');

        $conn		=	$GLOBALS['conn'];
        $db			=	$GLOBALS['db'];
        $validate_table	=	$GLOBALS['validate_table'];
        $from		=	$GLOBALS['admin_email'];
        $wifidb_smtp	=	$GLOBALS['wifidb_smtp'];
        $sender		=	$from;
        $sender_pass	=	$GLOBALS['wifidb_from_pass'];
        $UPATH		=	$GLOBALS['UPATH'];
        $mail		=	new MAIL5();
        $sec		=	new security();
        $date		=	date("Y-m-d H:i:s");
        if(!$mail->from($from))
        {die("Failed to add From address\r\n");}

        if(!$mail->addto($to))
        {die("Failed to add To address\r\n");}

        $subject = "WifiDB New User Validation";
        if(!$mail->subject($subject))
        {die("Failed to add subject\r\n");}

        $validate_code = $sec->gen_keys(48);

        $contents = "The Administrator of This WiFiDB has enabled user validation before you can use your login.
    Your account: $username\r\n
    Validation Link: $UPATH/login.php?func=validate_user&validate_code=$validate_code

    -WiFiDB Service";
        if(!$mail->text($contents))
        {echo "Failed to add Message"; return 0;}

        $smtp_conn = $mail->connect($wifidb_smtp, 465, $sender, $sender_pass, 'tls', 10);
        if ($smtp_conn)
        {
            $smtp_send = $mail->send($smtp_conn);
            if($smtp_send)
            {
                $insert = "INSERT INTO `$db`.`$validate_table` (`username`, `code`, `date`) VALUES ('$username', '$validate_code', '$date')";
        #	echo $insert."<BR>";
                if($this->sql->conn->query($insert))
                {
                    $return =1;
                #	echo "Message sent and inserted into DB.";
                }else
                {
                    $insert = "UPDATE `$db`.`$validate_table` SET `code` = '$validate_code', `date` = '$date' WHERE `username` LIKE '$username' LIMIT 1";
            #	echo $insert."<BR>";
                    if($this->sql->conn->query($insert))
                    {
                        $return =1;
                    #	echo "Message sent and Updated to newest data.";
                    }else
                    {
                        $return = 0;
                        echo "Message sent, but insert into table failed.";
                    }
                }
            }
            else
            {
                $return = 0;
                echo "Failed to send message.";
            }
        }else
        {
            $return = 0;
            echo "Failed to connect to SMTP server";
        }
        $mail->disconnect();
        return $return;
    }
}
?>
