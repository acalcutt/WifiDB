<?php
// A stand-in OAuth provider for test-linked-logins.inc.sh and test-mail.inc.sh, run in the container with
// php -S 127.0.0.1:8099 on this file. /authorize sends the browser straight back, as if whoever /tmp/oauth-subject names
// had signed in; /token trades the code for an access token and a refresh token, once, checking the client, the secret
// (fake-secret), the redirect URI and, when PKCE was used, the verifier, and trades a refresh token for a new access token
// as long as it hasn't been revoked (/revoke); /userinfo says who it is, in the fields both Google's and GitHub's
// answers have.
$dir = '/tmp/oauth-fake';
@mkdir($dir);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');
switch($path)
{
	case '/authorize':
		$subject = trim((string)@file_get_contents('/tmp/oauth-subject'));
		$code = bin2hex(random_bytes(8));
		file_put_contents("$dir/code-$code", json_encode(array('subject' => $subject, 'client_id' => $_GET['client_id'],
			'redirect_uri' => $_GET['redirect_uri'], 'challenge' => isset($_GET['code_challenge']) ? $_GET['code_challenge'] : '')));
		$sep = strpos($_GET['redirect_uri'], '?') === false ? '?' : '&';
		header('Location: ' . $_GET['redirect_uri'] . $sep . http_build_query(array('code' => $code, 'state' => $_GET['state'])), true, 302);
		exit;

	case '/token':
		if(@$_POST['grant_type'] === 'refresh_token')
		{
			$kept = json_decode((string)@file_get_contents("$dir/refresh-" . basename((string)@$_POST['refresh_token'])), true);
			if(!is_array($kept) || @$_POST['client_id'] !== $kept['client_id'] || @$_POST['client_secret'] !== 'fake-secret')
			{
				http_response_code(400); echo json_encode(array('error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.')); exit;
			}
			$token = bin2hex(random_bytes(8));
			file_put_contents("$dir/token-$token", $kept['subject']);
			echo json_encode(array('access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => 3599));
			exit;
		}
		$code = basename(isset($_POST['code']) ? (string)$_POST['code'] : '');
		$kept = json_decode((string)@file_get_contents("$dir/code-$code"), true);
		@unlink("$dir/code-$code");
		$ok = is_array($kept) && @$_POST['client_id'] === $kept['client_id'] && @$_POST['client_secret'] === 'fake-secret'
			&& @$_POST['redirect_uri'] === $kept['redirect_uri'];
		if($ok && $kept['challenge'] !== '')
		{
			$ok = rtrim(strtr(base64_encode(hash('sha256', (string)@$_POST['code_verifier'], true)), '+/', '-_'), '=') === $kept['challenge'];
		}
		if(!$ok) { http_response_code(400); echo json_encode(array('error' => 'invalid_grant')); exit; }
		$token = bin2hex(random_bytes(8));
		file_put_contents("$dir/token-$token", $kept['subject']);
		$refresh = bin2hex(random_bytes(8));
		file_put_contents("$dir/refresh-$refresh", json_encode(array('subject' => $kept['subject'], 'client_id' => $kept['client_id'])));
		echo json_encode(array('access_token' => $token, 'token_type' => 'Bearer', 'refresh_token' => $refresh));
		exit;

	case '/revoke':
		@unlink("$dir/refresh-" . basename((string)@$_POST['token']));
		echo '{}';
		exit;

	case '/userinfo':
		$headers = function_exists('getallheaders') ? getallheaders() : array();
		$auth = isset($headers['Authorization']) ? $headers['Authorization'] : (string)@$_SERVER['HTTP_AUTHORIZATION'];
		$subject = @file_get_contents("$dir/token-" . basename(preg_replace('/^Bearer /', '', $auth)));
		if($subject === false) { http_response_code(401); echo '{}'; exit; }
		echo json_encode(array('sub' => $subject, 'id' => $subject, 'email' => "$subject@fake.example", 'name' => "Person $subject", 'login' => "person$subject"));
		exit;
}
http_response_code(404);
