<?php
// Ensure init knows this is a CP/HTML request
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");
include('../lib/init.inc.php');

header('Content-Type: application/json');

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
if (!$token) {
    http_response_code(400);
    echo json_encode(array('error' => 'missing_token'));
    exit;
}

// lookup token
$sql = "SELECT * FROM link_tokens WHERE token = ? LIMIT 1";
try {
    $stmt = $dbcore->sql->conn->prepare($sql);
    $stmt->bindParam(1, $token, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch();
} catch(Exception $e) {
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

// fetch user apikey
$sql2 = "SELECT apikey, username FROM user_info WHERE id = ? LIMIT 1";
$stmt2 = $dbcore->sql->conn->prepare($sql2);
$stmt2->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt2->execute();
$urow = $stmt2->fetch();
if (!$urow) {
    http_response_code(404);
    echo json_encode(array('error' => 'user_not_found'));
    exit;
}

// mark token used
$upd = "UPDATE link_tokens SET used = 1 WHERE id = ?";
$ustmt = $dbcore->sql->conn->prepare($upd);
$ustmt->bindParam(1, $row['id'], PDO::PARAM_INT);
$ustmt->execute();

// return apikey (one-time)
echo json_encode(array('username' => $urow['username'], 'apikey' => $urow['apikey']));
exit;

?>
