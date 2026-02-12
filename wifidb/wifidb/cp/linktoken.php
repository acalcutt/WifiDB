<?php
// Ensure init knows we're running an HTML/CP request
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");
include('../lib/init.inc.php');

header('Content-Type: application/json');

$username = $dbcore->sec->LoginUser;
if (!$username) {
    http_response_code(403);
    echo json_encode(array('error' => 'not_logged_in'));
    exit;
}

// find user id
if($dbcore->sql->service == "mysql")
    {$sql0 = "SELECT * FROM user_info WHERE username = ? LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
    {$sql0 = "SELECT TOP 1 * FROM user_info WHERE username = ?";}
$result = $dbcore->sql->conn->prepare($sql0);
$result->bindParam(1, $username, PDO::PARAM_STR);
$result->execute();
$userArray = $result->fetch();
if (!$userArray) {
    http_response_code(404);
    echo json_encode(array('error' => 'user_not_found'));
    exit;
}
$user_id = $userArray['id'];

// create table if needed (MySQL)
if($dbcore->sql->service == "mysql") {
    $create = "CREATE TABLE IF NOT EXISTS link_tokens (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(64) NOT NULL,
        user_id INT NOT NULL,
        expires DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    try { $dbcore->sql->conn->exec($create); } catch(Exception $e) { }
}

// generate token
try {
    $token = bin2hex(random_bytes(16));
} catch(Exception $e) {
    $token = sha1(uniqid('', true));
}
$expiry_seconds = 300; // 5 minutes
$expires = date('Y-m-d H:i:s', time() + $expiry_seconds);
$created = date('Y-m-d H:i:s');
$ins = "INSERT INTO link_tokens (token, user_id, expires, used, created_at) VALUES (?, ?, ?, 0, ?);";

// cleanup expired tokens (remove rows where expires is in the past)
try {
    $now = date('Y-m-d H:i:s');
    $del = "DELETE FROM link_tokens WHERE expires < ?";
    $dstmt = $dbcore->sql->conn->prepare($del);
    $dstmt->bindParam(1, $now, PDO::PARAM_STR);
    $dstmt->execute();
} catch(Exception $e) {
    // non-fatal - ignore cleanup errors
}

$ins = "INSERT INTO link_tokens (token, user_id, expires, used, created_at) VALUES (?, ?, ?, 0, ?);";
$stmt = $dbcore->sql->conn->prepare($ins);
$stmt->bindParam(1, $token, PDO::PARAM_STR);
$stmt->bindParam(2, $user_id, PDO::PARAM_INT);
$stmt->bindParam(3, $expires, PDO::PARAM_STR);
$stmt->bindParam(4, $created, PDO::PARAM_STR);
if(!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(array('error' => 'db_insert_failed'));
    exit;
}

$redeem_url = $dbcore->wifidb_host_url . 'cp/redeem_link.php?token=' . urlencode($token);

echo json_encode(array('token' => $token, 'expires' => $expires, 'redeem_url' => $redeem_url));
exit;

?>
