<?php
// include/enrollLesson.inc.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['sUser'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Not logged in']);
  exit;
}

// Look up user_id and subscription info from session username
$username = $_SESSION['sUser'];
$stmt = $connection->prepare("SELECT user_id, role, subscription_type FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || !($u = $res->fetch_assoc())) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'User not found']);
  exit;
}
$user_id = (int)$u['user_id'];
$user_role = $u['role'];
$subscription_type = $u['subscription_type'];

// Get lesson_id from POST
$lesson_id = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;
if ($lesson_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing/invalid lesson_id']);
  exit;
}

// NEW: Check if user is allowed to enroll in this lesson
$stmt = $connection->prepare("SELECT author_id FROM tbl_lesson WHERE lesson_id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson_result = $stmt->get_result();
if (!$lesson_result || !($lesson = $lesson_result->fetch_assoc())) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Lesson not found']);
  exit;
}

// NEW: Premium check - if user is Gakusei, not premium, and lesson has author_id
if ($user_role === 'Gakusei' && $subscription_type !== 'Premium' && $lesson['author_id'] !== null) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Premium subscription required for this lesson']);
  exit;
}

// Insert enrollment (ignore duplicates)
$sql = "INSERT IGNORE INTO tbl_user_enrollments (user_id, lesson_id) VALUES (?, ?)";
$ins = $connection->prepare($sql);
$ins->bind_param("ii", $user_id, $lesson_id);
$ok = $ins->execute();

if (!$ok) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'DB insert failed']);
  exit;
}

echo json_encode(['ok' => true]);