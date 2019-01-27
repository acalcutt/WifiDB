<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2019 Andrew Calcutt, 2011 Phil Ferland

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
    function __construct($config)
    {
		$this->HOSTURL				  = $config['hosturl'];
		$this->root					 = $config['root'];
		$this->URL_PATH				 = $this->HOSTURL.$this->root.'/';
		$this->wifidb_email_updates	 	= $config['wifidb_email_updates'];
		$this->email_validation		 	= $config['email_validation'];
		$this->admin_email				= $config['admin_email'];
		$this->wifidb_from				= $config['wifidb_from'];
		$this->wifidb_from_pass			= $config['wifidb_from_pass'];
		$this->wifidb_smtp				= $config['wifidb_smtp'];
		$this->wifidb_smtp_port			= $config['wifidb_smtp_port'];
		
		$this->sec					  = new security($this, $config);
		$this->sql					  = new SQL($config);
		
		$this->mail = new PHPMailer();
		$this->mail->SMTPDebug = 0;                                 // Enable verbose debug output 0:disable 2:verbose
		$this->mail->isSMTP();                                      // Set mailer to use SMTP
		$this->mail->Host = $this->wifidb_smtp;					// Specify main and backup SMTP servers
		$this->mail->SMTPAuth = false;                               // Enable SMTP authentication
		$this->mail->admin_email = $this->admin_email;               // Admin email address
		$this->mail->Username = $this->wifidb_from;               // SMTP username
		$this->mail->Password = $this->wifidb_from_pass;                          // SMTP password
		$this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
		$this->mail->Port = $this->wifidb_smtp_port;  
		$this->mail->setFrom($this->wifidb_from, 'WifiDB');
		$this->mail->AddReplyTo($this->admin_email, 'WifiDB');
		$this->mail->SMTPOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
    }
    
    function mail_password_reset($username = "", $Useremail = 'noone@somewhere.local')
    {
        
        ##########################
        $validatecode = $this->GenerateKey(12);
        ##########################
        if(!$this->mail->from($this->wifidb_from))
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
                $update = "UPDATE `user_info` SET `password` = '$password' WHERE `username` = '$username'";
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
    #   Filtering of Mail to User privileged type    #
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
            if($this->wifidb_email_updates)
            {
				#Create Email Subject and Body
				if($error_f){$subject .= " ^*^*^*^ ERROR! ^*^*^*^";}
				$this->mail->Subject = $subject;
				$this->mail->Body    = $contents;

				#Get users this email will be sent to
				$sql = "SELECT `email`, `username` FROM `user_info` WHERE `disabled` = '0' AND `validated` = '0'";
                $sql .= $this->sql_type_mail_filter($type);
                if($error_f)
                {
                    $sql .= " AND `admin` = '1'";
                }
                if(!$error_f){$sql .= " AND `username` NOT LIKE 'admin%'";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->execute();
				while ($userArray = $prep->fetch(2))
				{
					#Add recipient
					$this->mail->addAddress($userArray['email']);

					#Send email
					if (!$this->mail->send()) {
						echo "Mailer Error (" . str_replace("@", "&#64;", $userArray['email']) . ') ' . $this->mail->ErrorInfo . '<br />';
						return 0;
					}
					else
					{
						//echo "Email sent to: ".$userArray['email']."\r\n";
					}
					
					#Clear recipient
					$this->mail->clearAddresses();
				}
				return 1;
            }
			else
            {
                #echo "Mail updates are turned off.";
                return 1;
            }
        }
		else
        {
            echo "$"."type var is not set, check your code.<br>\r\n";
            return 0;
        }
    }

    #===================================#
    #   Email for user verification     #
    #===================================#
    function mail_validation($function = 'validate_user', $to = '', $username = '', $message = '', $subject)
    {
		$date				=	date("Y-m-d H:i:s");
		$validate_code = $this->sec->GenerateKey(48);
		
		$contents = $message."\r\n";
		$contents .= "Your account: $username \r\n";
		$contents .= "Validation Link: $this->URL_PATH/login.php?func=".$function."&username=$username&validate_code=$validate_code \r\n\r\n";
		$contents .= "---- Vistumbler WiFiDB ( https://live.wifidb.net ) ----";
	
		try 
		{
			$this->mail->addAddress($to);     // Add a recipient
			$this->mail->Subject = $subject;
			$this->mail->Body    = $contents;
			$this->mail->send();
		} 
		catch (Exception $e) 
		{
			echo 'Validation email could not be sent. Mailer Error: ', $this->mail->ErrorInfo;
			return 0;
		}

		$insert = "INSERT INTO `user_validate` (`username`, `code`, `date`) VALUES ('$username', '$validate_code', '$date')";
		#echo $insert;
		if($this->sql->conn->query($insert))
		{
			$return =1;
			#echo "Message sent and inserted into DB.";
		}
		else
		{
			$insert = "UPDATE `user_validate` SET `code` = '$validate_code', `date` = '$date' WHERE `username` LIKE '$username' LIMIT 1";
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

        return $return;
    }
}
?>
