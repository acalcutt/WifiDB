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

// PHPMailer comes from Composer (pinned in composer.json); wifidb/lib/init.inc.php loads its autoloader.
use PHPMailer\PHPMailer\PHPMailer;

#[AllowDynamicProperties]
class wdbmail
{
	// Set by the constructor and the methods (PHP 8.2 deprecates making a property by assigning it)
	public $DKIM_copyHeaderFields, $DKIM_domain, $DKIM_identity, $DKIM_passphrase, $DKIM_private, $DKIM_selector,
		$email_validation, $ListUnsubscribe, $mail, $notifier, $sec, $signup_smtp_auth, $signup_smtp_authtype,
		$signup_smtp_debug, $signup_smtp_from, $signup_smtp_host, $signup_smtp_options, $signup_smtp_pass,
		$signup_smtp_port, $signup_smtp_replyto, $signup_smtp_secure, $signup_smtp_user, $signupmail, $smtp_auth,
		$smtp_authtype, $smtp_debug, $smtp_from, $smtp_host, $smtp_options, $smtp_pass, $smtp_port, $smtp_replyto,
		$smtp_secure, $smtp_user, $sql, $URL_PATH, $wifidb_email_updates, $wifidb_install, $XMailer;
    function __construct($config, $sql = null)
    {
		$this->wifidb_install			= isset($config['wifidb_install']) ? rtrim($config['wifidb_install'], '/\\').'/' : null;
		$this->sec					  	= new security($this, $config);
		$this->sql					  	= ($sql !== null) ? $sql : new SQL($config);
		$config							= $this->SiteMailConfig($config);
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

		$this->mail 					= self::Mailer($config);
		$this->mail->addCustomHeader("List-Unsubscribe",$this->ListUnsubscribe);

		$this->signup_smtp_debug		= $config['signup_smtp_debug'] ?? $config['smtp_debug'];
		$this->signup_smtp_host			= $config['signup_smtp_host'] ?? $config['smtp_host'];
		$this->signup_smtp_port			= $config['signup_smtp_port'] ?? $config['smtp_port'];
		$this->signup_smtp_user			= $config['signup_smtp_user'] ?? $config['smtp_user'];
		$this->signup_smtp_pass			= $config['signup_smtp_pass'] ?? $config['smtp_pass'];
		$this->signup_smtp_from			= $config['signup_smtp_from'] ?? $config['smtp_from'];
		$this->signup_smtp_replyto		= $config['signup_smtp_replyto'] ?? $config['smtp_replyto'];		
		$this->signup_smtp_secure		= $config['signup_smtp_secure'] ?? $config['smtp_secure'];
		$this->signup_smtp_auth			= $config['signup_smtp_auth'] ?? $config['smtp_auth'];
		$this->signup_smtp_authtype		= $config['signup_smtp_authtype'] ?? $config['smtp_authtype'];
		$this->signup_smtp_options		= $config['signup_smtp_options'] ?? $config['smtp_options'];

		$this->signupmail 				= self::Mailer($config, true);
    }

	/** config.inc.php's mail settings with what an administrator saved on admin/mail.php over them (lib/site_settings.inc.php) */
	private function SiteMailConfig($config)
	{
		if(!class_exists('SiteSettings', false))
		{
			require_once ($this->wifidb_install !== null ? $this->wifidb_install : dirname(__DIR__, 3).'/wifidb/').'lib/site_settings.inc.php';
		}
		// with no database to read (a test), config.inc.php's alone
		return (new SiteSettings(isset($this->sql->conn) ? $this->sql->conn : null, $config))->MailConfig($config);
	}

	/**
	 * A PHPMailer for the main account, or with $signup the one sign-up and password-reset mail goes out from: any
	 * signup_smtp_* setting it doesn't have is the main account's. Both are signed with the same DKIM key (it is the same
	 * domain; only the account differs), so sign-up mail is not the one kind that goes out unsigned: the key saved on
	 * admin/mail.php, else the file DKIM_private names.
	 */
	public static function Mailer(array $config, $signup = false)
	{
		$c = function ($name, $default = '') use ($config) { return isset($config[$name]) ? $config[$name] : $default; };
		$smtp = function ($name, $default = '') use ($config, $signup, $c) { return ($signup && isset($config['signup_'.$name])) ? $config['signup_'.$name] : $c($name, $default); };

		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->XMailer			= $c('XMailer');			// What to put in the X-Mailer header. An empty string for PHPMailer default, whitespace for none, or a string to use.
		$mail->SMTPDebug		= (int)$smtp('smtp_debug', 0);	// 0:disable 2:verbose
		$mail->Host				= $smtp('smtp_host');
		$mail->Port				= (int)$smtp('smtp_port', 587);
		$mail->Username			= $smtp('smtp_user');
		$mail->Password			= $smtp('smtp_pass');
		$mail->setFrom($smtp('smtp_from'), $c('smtp_from_name'));
		if($smtp('smtp_replyto') !== '') { $mail->addReplyTo($smtp('smtp_replyto')); }
		$mail->SMTPSecure		= $smtp('smtp_secure');		// tls (STARTTLS) or ssl
		$mail->SMTPAuth			= (bool)$smtp('smtp_auth', true);
		$mail->AuthType			= $smtp('smtp_authtype');	// '': the best the server offers
		$mail->SMTPOptions		= $smtp('smtp_options', array());
		// a Google account connected on admin/mail.php (lib/mail_oauth.inc.php) instead of the password: the sign-up
		// account's own if it has one connected, else the main account's
		if($signup && !empty($config['signup_smtp_oauth_token'])) { $mail->AuthType = 'XOAUTH2'; }
		if($mail->AuthType === 'XOAUTH2')
		{
			if(!class_exists('MailOAuth', false))
			{
				require_once (isset($config['wifidb_install']) ? rtrim($config['wifidb_install'], '/\\').'/' : dirname(__DIR__, 3).'/wifidb/').'lib/mail_oauth.inc.php';
			}
			$mail->SMTPAuth = true;
			$mail->setOAuth(new MailOAuth($mail->Username, $c('smtp_oauth_client_id'), $c('smtp_oauth_secret'), $smtp('smtp_oauth_token'),
				isset($config['mail_oauth_endpoints']) ? (array)$config['mail_oauth_endpoints'] : array()));
		}
		// PHPMailer waits 300 seconds for a mail server that doesn't answer, far past the web pool's 30: the page (a
		// sign-up, a password reset) would end in an error instead of saying the mail couldn't be sent
		$mail->Timeout			= (int)$c('smtp_timeout', 15);

		$mail->DKIM_domain		= $c('DKIM_domain');
		$mail->DKIM_private_string = $c('DKIM_private_string');
		$mail->DKIM_private		= $c('DKIM_private');
		$mail->DKIM_selector	= $c('DKIM_selector');
		$mail->DKIM_passphrase	= $mail->DKIM_private_string !== '' ? '' : $c('DKIM_passphrase');	// a saved key has none
		$mail->DKIM_identity	= $c('DKIM_identity');		// usually the From address
		$mail->DKIM_copyHeaderFields = (bool)$c('DKIM_copyHeaderFields', true);
		return $mail;
	}

	/**
	 * Sends a test message from the main account, or the sign-up one, as Mailer sets it up.
	 * @return array (sent?, PHPMailer's error or '', the conversation with the mail server: the commands and the server's
	 *   answers, but not the login (base64 is not encryption) or the message)
	 */
	public static function Test(array $config, $to, $signup = false)
	{
		$mail = self::Mailer($config, $signup);
		$lines = array();
		$mail->SMTPDebug = 2;
		$mail->Debugoutput = function ($line) use (&$lines)
		{
			$line = rtrim($line);
			if(preg_match('/^CLIENT -> SERVER: (.*)$/s', $line, $m))
			{
				if(preg_match('/^AUTH (\S+)/i', $m[1], $a)) { $lines[] = 'CLIENT -> SERVER: AUTH '.$a[1].' (the login is left out)'; }
				elseif(preg_match('/^(EHLO|HELO|STARTTLS|MAIL FROM:|RCPT TO:|DATA|QUIT|RSET)/i', $m[1])) { $lines[] = $line; }
				return;
			}
			if($line !== '') { $lines[] = $line; }
		};
		$mail->addAddress($to);
		$mail->Subject = 'WiFiDB mail test';
		$mail->Body = "This is a test message from WiFiDB's mail settings.\r\nIf you can read it, sending works.\r\n";
		try
		{
			$sent = $mail->send();
		}
		catch(Throwable $e)
		{
			$sent = false;
			$mail->ErrorInfo = $e->getMessage();
		}
		return array($sent, $sent ? '' : self::Why($mail), $lines);
	}

	/** Why $mail wasn't sent: PHPMailer's error, and Google's reason when the access token is what failed */
	public static function Why(PHPMailer $mail)
	{
		$oauth = $mail->getOAuth();
		return ($oauth instanceof MailOAuth && $oauth->Error() !== '') ? $oauth->Error().' ('.$mail->ErrorInfo.')' : $mail->ErrorInfo;
	}

	/**
	 * For the mail check: whether Google gives each account that signs in with a Google account an access token.
	 * @return array [status, text], as MailCheck::Check's
	 */
	public static function CheckOAuth(array $config)
	{
		$out = array();
		$seen = array();
		foreach(array(false, true) as $signup)
		{
			$oauth = self::Mailer($config, $signup)->getOAuth();
			if(!($oauth instanceof MailOAuth)) { continue; }
			$which = $signup ? 'The sign-up account' : 'The mail account';
			$key = $oauth->User()."\n".($signup && !empty($config['signup_smtp_oauth_token']) ? 'own' : 'main');
			if(isset($seen[$key])) { continue; }   # the sign-up account using the main one's
			$seen[$key] = true;
			try
			{
				$oauth->AccessToken();
				$out[] = array('ok', "$which signs in to Google as ".$oauth->User().': Google gives it an access token.');
			}
			catch(Throwable $e)
			{
				$out[] = array('fail', "$which can't send: ".$oauth->Error());
			}
		}
		return $out;
	}
    
    function mail_password_reset($username = "", $Useremail = 'noone@somewhere.local')
    {
        
        ##########################
        $validatecode = $this->GenerateKey(12);
        ##########################
        if(!$this->signupmail->from($this->wifidb_from))
        {die("Failed to add From address\r\n");}
        if(!$this->signupmail->addto($Useremail))
        {die("Failed to add To address\r\n");}

        if(!$this->signupmail->subject("WiFiDB User Password Reset"))
        {die("Failed to add subject\r\n");}

        $contents = "
You have requested a reset of your password, here it is...
Your account: $username
Temp Password: $validatecode

Go here to reset it to one you choose:
".$GLOBALS['UPATH']."/login.php?func=reset_password

-WiFiDB Service";

        if(!$this->signupmail->text($contents))
        {die("Failed to add message\r\n");}

        $smtp_conn = $this->signupmail->connect($this->smtp, 465, $sender, $sender_pass, 'tls', 10);
        if ($smtp_conn)
        {
            if($this->signupmail->send($smtp_conn))
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
        $this->signupmail->disconnect();
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
    #   notify (an alert in the Inbox, and e-mail for whoever   #
    #   chose it under Control Panel -> Notifications)          #
    #===========================================================#
    /**
     * @param string $type    a Notifier type (import_started, import_finished, import_failed, export, new_user, admin_alert)
     * @param array|null $users members the notification is about; null lets the type decide
     * @param string|null $title short heading for the alert (the e-mail subject is used if none)
     * @return int members given the alert
     */
    function notify($type, $subject, $message, $link = '', $users = null, $title = null)
    {
        try
        {
            $who = $this->notifier()->Deliver($type, $title !== null ? $title : $subject, $message, $link, $users);
        }
        catch (Throwable $e)
        {
            // A notification that cannot be stored must not stop the import or export that raised it.
            echo "Notification not stored: ".$e->getMessage()."\r\n";
            return 0;
        }
        $this->mail_members($who['email'], $subject, $message);
        return count($who['inbox']);
    }

    /** Tells the administrators something went wrong: an alert, and e-mail for those who chose it. */
    function alert($subject, $message, $link = '')
    {
        try
        {
            $who = $this->notifier()->Deliver('admin_alert', $subject, $message, $link);
        }
        catch (Throwable $e)
        {
            echo "Notification not stored: ".$e->getMessage()."\r\n";
            return 0;
        }
        return $this->mail_members($who['email'], $subject." ^*^*^*^ ERROR! ^*^*^*^", $message);
    }

    /**
     * E-mails these members (enabled ones with an address), one message each; one refused address doesn't stop the
     * rest. @return int 1 if every one went (or there was no one, or mail updates are off), 0 if any failed
     */
    function mail_members($usernames, $subject, $contents)
    {
        if(!$usernames || !$this->wifidb_email_updates) { return 1; }
        $contents .= "\r\nVistumbler WiFiDB (".$this->URL_PATH.") \r\n";
        $contents .= "To stop receiving these messages, choose what is e-mailed under Control Panel -> Notifications: ".$this->URL_PATH."cp/index.php?func=notifications";
        $this->mail->Subject = $subject;
        $this->mail->Body    = $contents;
        $allSent = 1;
        $prep = $this->sql->conn->prepare("SELECT email FROM user_info WHERE username = ? AND disabled = '0' AND validated = '0'");
        foreach($usernames as $username)
        {
            $prep->execute(array($username));
            $email = (string)$prep->fetchColumn();
            $prep->closeCursor();
            if($email === '') { continue; }
            try
            {
                $this->mail->addAddress($email);
                if (!$this->mail->send()) {
                    echo "Mailer Error (" . str_replace("@", "&#64;", $email) . ') ' . self::Why($this->mail) . '<br />';
                    $allSent = 0;
                }
            }
            catch (Exception $e)
            {
                echo "Mailer Error (" . str_replace("@", "&#64;", $email) . ') ' . $e->getMessage() . '<br />';
                $allSent = 0;
            }
            $this->mail->clearAddresses();
        }
        return $allSent;
    }

    function notifier()
    {
        if(!isset($this->notifier))
        {
            if(!class_exists('Notifier', false))
            {
                $file = ($this->wifidb_install !== null ? $this->wifidb_install : dirname(__DIR__, 3).'/wifidb/').'lib/notify.inc.php';
                require_once $file;
            }
            $this->notifier = new Notifier($this->sql->conn);
        }
        return $this->notifier;
    }

    #===========================================================#
    #   mail_users (Emails the admins of the DB about updates)  #
    #===========================================================#
    function mail_users($contents = '', $subject = "WifiDB Notifications", $type = "none", $error_f = 0)
    {
        if($type != 'none' and $type != '')
        {
            if($this->wifidb_email_updates)
            {
				#Add Signature, Unsubscribe
				$contents .= "\r\nVistumbler WiFiDB (".$this->URL_PATH.") \r\n";
				$contents .= "To stop receiving these messages, choose what is e-mailed under Control Panel -> Notifications: ".$this->URL_PATH."cp/index.php?func=notifications";

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
				// One member's bad address or a refused message must not stop everyone after them from being told.
				$allSent = 1;
				while ($userArray = $prep->fetch(2))
				{
					try
					{
						#Add recipient
						$this->mail->addAddress($userArray['email']);

						#Send email
						if (!$this->mail->send()) {
							echo "Mailer Error (" . str_replace("@", "&#64;", $userArray['email']) . ') ' . self::Why($this->mail) . '<br />';
							$allSent = 0;
						}
					}
					catch (Exception $e)
					{
						echo "Mailer Error (" . str_replace("@", "&#64;", $userArray['email']) . ') ' . $e->getMessage() . '<br />';
						$allSent = 0;
					}

					#Clear recipient
					$this->mail->clearAddresses();
				}
				return $allSent;
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
    function mail_validation($function, $to, $username, $message, $subject)
    {
		$date				=	date("Y-m-d H:i:s");
		$validate_code = $this->sec->GenerateKey(48);
		
		$contents = $message."\r\n";
		$contents .= "Your account: $username \r\n";
		$contents .= "Validation Link: ".$this->URL_PATH."login.php?func=".$function."&username=$username&validate_code=$validate_code \r\n";
		$contents .= "\r\nVistumbler WiFiDB (".$this->URL_PATH.")";
	
		// send() says it failed by returning false (this PHPMailer doesn't throw): the member must be told the mail isn't
		// coming, not to wait for it
		try
		{
			$this->signupmail->addAddress($to);     // Add a recipient
			$this->signupmail->Subject = $subject;
			$this->signupmail->Body    = $contents;
			$sent = $this->signupmail->send();
		}
		catch (Exception $e)
		{
			$sent = false;
		}
		$this->signupmail->clearAddresses();
		if(!$sent)
		{
			$this->sec->logd('Validation email to '.$username.' could not be sent. Mailer Error: '.self::Why($this->signupmail), 'error');
			return 0;
		}
		
		if($this->sql->service == "mysql")
		{
			// bound, not pasted in: the username comes from the sign-up form
			try { $inserted = $this->sql->conn->prepare("INSERT INTO user_validate (username, code, date) VALUES (?, ?, ?)")->execute(array($username, $validate_code, $date)); }
			catch (Exception $e) { $inserted = false; }
			if($inserted)
			{
				$return =1;
				#echo "Message sent and inserted into DB.";
			}
			else
			{
				try { $updated = $this->sql->conn->prepare("UPDATE user_validate SET code = ?, date = ? WHERE username = ?")->execute(array($validate_code, $date, $username)); }
				catch (Exception $e) { $updated = false; }
				if($updated)
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
		else if($this->sql->service == "pgsql")
		{
			// Postgres equivalent of the MERGE below. user_validate.username already
			// carries a unique constraint (user_validate$username), so it can be the
			// conflict target directly.
			$retry = true;
			while ($retry)
			{
				try
				{
					$sql = "INSERT INTO user_validate (username, code, \"date\")\n"
						. "VALUES (:username, :code, CURRENT_TIMESTAMP)\n"
						. "ON CONFLICT (username) DO UPDATE SET \"date\" = CURRENT_TIMESTAMP, code = :ucode\n"
						. "RETURNING id";

					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(':ucode', $validate_code, PDO::PARAM_STR);
					$prep->bindParam(':username', $username, PDO::PARAM_STR);
					$prep->bindParam(':code', $validate_code, PDO::PARAM_STR);
					$prep->execute();
					$return = $prep->fetch(2);
					$retry = false;
				}
				catch (Exception $e)
				{
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
					$return = 0;
				}
			}
		}
		else if($this->sql->service == "sqlsrv")
		{
			$retry = true;
			while ($retry)
			{
				try
				{
					$sql = "MERGE INTO user_validate WITH (HOLDLOCK)\n"
						. "	USING (SELECT :susername AS susername) AS newvald\n"
						. "		ON user_validate.username = newvald.susername\n"
						. "	WHEN MATCHED THEN\n"
						. "		UPDATE SET user_validate.date = getdate(), user_validate.code = :ucode\n"
						. "	WHEN NOT MATCHED THEN\n"
						. "		INSERT ([username], [code], [date])\n"
						. "		VALUES (:username, :code, getdate())\n"
						. 'OUTPUT INSERTED.id;';
						
					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(':susername', $username, PDO::PARAM_STR);
					$prep->bindParam(':ucode', $validate_code, PDO::PARAM_STR);
					$prep->bindParam(':username', $username, PDO::PARAM_STR);
					$prep->bindParam(':code', $validate_code, PDO::PARAM_STR);
					$prep->execute();
					$return = $prep->fetch(2);
					$retry = false;
				}
				catch (Exception $e) 
				{
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
					$return = 0;
				}
			}
		}



        return $return;
    }
}
?>
