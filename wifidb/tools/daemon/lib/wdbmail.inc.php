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
		$this->URL_PATH				 	= $config['hosturl'].$config['root'].'/';
		$this->wifidb_email_updates	 	= $config['wifidb_email_updates'];
		$this->email_validation		 	= $config['email_validation'];
		$this->smtp_debug				= $config['smtp_debug'];
		$this->smtp_host				= $config['smtp_host'];
		$this->smtp_port				= $config['smtp_port'];
		$this->smtp_user				= $config['smtp_user'];
		$this->smtp_pass				= $config['smtp_pass'];
		$this->smtp_from				= $config['smtp_from'];
		$this->smtp_replyto				= $config['smtp_replyto'];		
		$this->smtp_secure				= $config['smtp_secure'];
		$this->smtp_auth				= $config['smtp_auth'];
		$this->smtp_authtype			= $config['smtp_authtype'];
		$this->smtp_options				= $config['smtp_options'];
		$this->DKIM_domain				= $config['DKIM_domain'];
		$this->DKIM_private				= $config['DKIM_private'];
		$this->DKIM_selector			= $config['DKIM_selector'];
		$this->DKIM_passphrase			= $config['DKIM_passphrase'];
		$this->DKIM_identity			= $config['DKIM_identity'];
		$this->DKIM_copyHeaderFields	= $config['DKIM_copyHeaderFields'];
		$this->ListUnsubscribe			= $config['ListUnsubscribe'];
		$this->XMailer					= $config['XMailer'];
		
		$this->sec					  	= new security($this, $config);
		$this->sql					  	= new SQL($config);
		
		$this->mail 					= new PHPMailer();
		$this->mail->isSMTP();										// Set mailer to use SMTP			
		$this->mail->XMailer 			= $this->XMailer;			//What to put in the X-Mailer header. An empty string for PHPMailer default, whitespace for none, or a string to use.		
		$this->mail->SMTPDebug 			= $this->smtp_debug;		// Enable verbose debug output 0:disable 2:verbose
		$this->mail->Host 				= $this->smtp_host;			// Specify main and backup SMTP servers
		$this->mail->Port 				= $this->smtp_port;			// Specify smtp port
		$this->mail->Username 			= $this->smtp_user;			// SMTP username
		$this->mail->Password 			= $this->smtp_pass;			// SMTP password		
		$this->mail->SetFrom($this->smtp_from);						// SMTP from
		$this->mail->AddReplyTo($this->smtp_replyto);				// SMTP reply to
		$this->mail->SMTPSecure 		= $this->smtp_secure;		// Enable TLS encryption, ssl also accepted		
		$this->mail->SMTPAuth 			= $this->smtp_auth;			// Enable SMTP authentication
		$this->mail->AuthType 			= $this->smtp_authtype;		// Auth type, tls or ssl
		$this->mail->SMTPOptions 		= $this->smtp_options;
		
		$this->mail->DKIM_domain 		= $this->DKIM_domain;
		$this->mail->DKIM_private 		= $this->DKIM_private;
		$this->mail->DKIM_selector 		= $this->DKIM_selector;		//Set this to your own selector
		$this->mail->DKIM_passphrase 	= $this->DKIM_passphrase;	//Put your private key's passphrase in here if it has one
		$this->mail->DKIM_identity 		= $this->DKIM_identity;		//The identity you're signing as - usually your From address
		$this->mail->DKIM_copyHeaderFields = $this->DKIM_copyHeaderFields;//Suppress listing signed header fields in signature, defaults to true for debugging purpose
		$this->mail->addCustomHeader("List-Unsubscribe",$this->ListUnsubscribe);
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
                $update = "UPDATE user_info SET password = '$password' WHERE username = '$username'";
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
                $sql = " AND schedule = '1'";
            break;

            case "import":
                $sql = " AND imports = '1'";
            break;

            case "kmz":
                $sql = " AND kmz = '1'";
            break;

            case "new_users":
                $sql = "AND new_users = '1'";
            break;

            case "statistics":
                $sql = " AND statistics = '1'";
            break;

            case "perfmon":
                $sql = " AND perfmon = '1'";
            break;

            case "announcements":
                $sql = " AND announcements = '1'";
            break;

            case "announce_comment":
                $sql = " AND announce_comment = '1'";
            break;

            case "pub_geocache":
                $sql = " AND pub_geocache = '1'";
            break;

            case "geonamed":
                $sql = " AND geonamed = '1'";
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
				#Add Signature, Unsubscribe
				$contents .= "\r\nVistumbler WiFiDB (".$this->URL_PATH.") \r\n";
				$contents .= "To stop receiving these messages, log into the WifiDB control panel at ".$this->URL_PATH."cp/?func=pref";

				#Create Email Subject and Body
				if($error_f){$subject .= " ^*^*^*^ ERROR! ^*^*^*^";}
				$this->mail->Subject = $subject;
				$this->mail->Body    = $contents;

				#Get users this email will be sent to
				$sql = "SELECT email, username FROM user_info WHERE disabled = '0' AND validated = '0'";
                $sql .= $this->sql_type_mail_filter($type);
                if($error_f)
                {
                    $sql .= " AND admin = '1'";
                }
                if(!$error_f){$sql .= " AND username NOT LIKE 'admin%'";}
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
		$contents .= "Validation Link: ".$this->URL_PATH."login.php?func=".$function."&username=$username&validate_code=$validate_code \r\n";
		$contents .= "\r\nVistumbler WiFiDB (".$this->URL_PATH.")";
	
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

		$insert = "INSERT INTO user_validate (username, code, date) VALUES ('$username', '$validate_code', '$date')";
		#echo $insert;
		if($this->sql->conn->query($insert))
		{
			$return =1;
			#echo "Message sent and inserted into DB.";
		}
		else
		{
			$insert = "UPDATE user_validate SET code = '$validate_code', date = '$date' WHERE username LIKE '$username'";
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
