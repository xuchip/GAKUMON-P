<?php
// include/getEnrollments.inc.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure user is logged in
if (!isset($_SESSION['sUser'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// Look up user_id using the username stored in the session
$username = $_SESSION['sUser'];
$stmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || !($u = $res->fetch_assoc())) {
    echo json_encode(['ok' => false, 'error' => 'User not found']);
    exit;
}
$user_id = (int)$u['user_id'];

try {
    $sql = "SELECT lesson_id FROM tbl_user_enrollments WHERE user_id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $lesson_ids = [];
    while ($row = $result->fetch_assoc()) {
        $lesson_ids[] = (int)$row['lesson_id'];
    }

    echo json_encode(['ok' => true, 'lesson_ids' => $lesson_ids]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
