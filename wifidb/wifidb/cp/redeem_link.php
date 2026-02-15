<?php
// Operate as HTML/cp so $dbcore is initialized as frontend, but pre-set
// the cookie-check flags so init.inc.php will not emit the JS timezone
// checker or exit early. This preserves expected $dbcore structure.
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

// Pretend the timezone cookie check already passed for programmatic clients.
// These don't actually send cookies to the browser, but they stop init.inc.php
// from trying to set them and exiting with the JS challenge.
$_COOKIE['wifidb_client_check'] = 1;
$_COOKIE['wifidb_client_timezone'] = 0;
$_COOKIE['wifidb_client_dst'] = 0;

header('Content-Type: application/json');

// Wrap main logic to convert any unexpected errors into JSON 500 responses
try {

    // Buffer any output from init so we can log and avoid leaking HTML/JS to client
    ob_start();
    try {
        include('../lib/init.inc.php');
    } catch (Throwable $ie) {
        $ibuf = ob_get_clean();
        if (!empty($ibuf)) {
            error_log("redeem_link.php init output on failure: " . str_replace("\n", " ", $ibuf));
        }
        error_log("redeem_link.php init exception: " . $ie->__toString());
        http_response_code(500);
        echo json_encode(array('error' => 'internal_error'));
        exit;
    }
    $init_buf = ob_get_clean();
    if (!empty($init_buf)) {
        error_log("redeem_link.php init output: " . str_replace("\n", " ", $init_buf));
    }

    // log masked incoming token and request info for diagnostics
    $raw_token = isset($_GET['token']) ? $_GET['token'] : '(none)';
    $mask = function($s) {
        if (strlen($s) <= 12) return $s;
        return substr($s,0,6) . '...' . substr($s,-6);
    };
    error_log("redeem_link.php request: method=" . ($_SERVER['REQUEST_METHOD'] ?? 'UNK') . " token=" . $mask($raw_token) . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? 'unk'));

    $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
if (!$token) {
    http_response_code(400);
    echo json_encode(array('error' => 'missing_token'));
    exit;
}

// lookup token (DB-dialect aware)
if (isset($dbcore->sql->service) && $dbcore->sql->service === 'sqlsrv') {
    $sql = "SELECT TOP 1 * FROM link_tokens WHERE token = ?";
} else {
    $sql = "SELECT * FROM link_tokens WHERE token = ? LIMIT 1";
}
try {
    $stmt = $dbcore->sql->conn->prepare($sql);
    $stmt->bindParam(1, $token, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
} catch(Exception $e) {
    error_log("redeem_link.php DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array('error' => 'db_error'));
    exit;
}

if (!$row) {
    http_response_code(404);
    echo json_encode(array('error' => 'token_not_found'));
    exit;
}

if ($row['used']) {
    http_response_code(410);
    echo json_encode(array('error' => 'token_already_used'));
    exit;
}

if (strtotime($row['expires']) < time()) {
    http_response_code(410);
    echo json_encode(array('error' => 'token_expired'));
    exit;
}

$user_id = $row['user_id'];

$stmt2_sql = null;
// fetch user apikey (DB-dialect aware)
if (isset($dbcore->sql->service) && $dbcore->sql->service === 'sqlsrv') {
    $stmt2_sql = "SELECT TOP 1 apikey, username FROM user_info WHERE id = ?";
} else {
    $stmt2_sql = "SELECT apikey, username FROM user_info WHERE id = ? LIMIT 1";
}
$stmt2 = $dbcore->sql->conn->prepare($stmt2_sql);
$stmt2->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt2->execute();
$urow = $stmt2->fetch();
if (!$urow) {
    http_response_code(404);
    echo json_encode(array('error' => 'user_not_found'));
    exit;
}

// If the user has no API key, generate one and persist it so the app can use it.
if (empty($urow['apikey'])) {
    try {
        $new_apikey = bin2hex(random_bytes(24));
    } catch (Exception $e) {
        $new_apikey = sha1(uniqid('', true));
    }
    try {
        $upd_apikey_sql = "UPDATE user_info SET apikey = ? WHERE id = ?";
        $upd_apikey_stmt = $dbcore->sql->conn->prepare($upd_apikey_sql);
        $upd_apikey_stmt->bindParam(1, $new_apikey, PDO::PARAM_STR);
        $upd_apikey_stmt->bindParam(2, $user_id, PDO::PARAM_INT);
        $upd_apikey_stmt->execute();
        // reflect in the returned row
        $urow['apikey'] = $new_apikey;
    } catch (Exception $e) {
        error_log("redeem_link.php failed to persist new apikey: " . $e->getMessage());
        // continue without failing — the app will see missing apikey
    }
}

// mark token used
$upd = "UPDATE link_tokens SET used = 1 WHERE id = ?";
$ustmt = $dbcore->sql->conn->prepare($upd);
$ustmt->bindParam(1, $row['id'], PDO::PARAM_INT);
try {
    $ustmt->execute();
} catch (Exception $e) {
    error_log("redeem_link.php DB update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array('error' => 'db_error'));
    exit;
}


// return apikey (one-time)
echo json_encode(array('username' => $urow['username'], 'apikey' => $urow['apikey']));
exit;

} catch (Throwable $t) {
    error_log("redeem_link.php exception: " . $t->__toString());
    http_response_code(500);
    echo json_encode(array('error' => 'internal_error'));
    exit;
}

?>
