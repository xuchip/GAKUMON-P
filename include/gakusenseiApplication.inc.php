<?php
// include/gakusensei_application.inc.php
declare(strict_types=1);
ini_set('display_errors', '0');          // prevent notices from polluting JSON
ini_set('log_errors', '1');              // log to server error log instead
error_reporting(E_ALL);                  // still log everything
header('Content-Type: application/json'); // set BEFORE ANY OUTPUT
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php'; // <-- make sure this sets $connection (mysqli)

if (!isset($_SESSION['sUser'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'Not authenticated']);
  exit;
}

// Find user_id from session username
$username = $_SESSION['sUser'];
$stmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'message' => 'User not found']);
  exit;
}
$user_id = (int)$user['user_id'];

// Collect fields from the form
$educ      = trim($_POST['education'] ?? '');
$school    = trim($_POST['school'] ?? '');
$expertise = trim($_POST['expertise'] ?? '');

// Basic validation
if ($educ === '' || $school === '' || $expertise === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'message' => 'Please complete all fields.']);
  exit;
}

// Proof upload (required by schema)
if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'message' => 'Please upload a valid proof file.']);
  exit;
}

$allowed = ['pdf','png','jpg','jpeg','webp'];
$ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'message' => 'Invalid file type. Allowed: pdf, png, jpg, jpeg, webp.']);
  exit;
}

// Save file
$uploadsDir = __DIR__ . '/../IMG/Applications';
if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);

$basename  = sprintf('proof_u%s_%s.%s', $user_id, bin2hex(random_bytes(6)), $ext);
$destPath  = $uploadsDir . '/' . $basename;
$publicUrl = 'IMG/Applications/' . $basename;

if (!move_uploaded_file($_FILES['proof']['tmp_name'], $destPath)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Failed to store uploaded file.']);
  exit;
}

// Optional: prevent duplicate pending/approved applications
$dupe = $connection->prepare(
  "SELECT application_id FROM tbl_creator_applications 
   WHERE user_id = ? AND status IN ('pending','approved') LIMIT 1"
);
$dupe->bind_param("i", $user_id);
$dupe->execute();
$dupe->store_result();
if ($dupe->num_rows > 0) {
  $dupe->close();
  http_response_code(409);
  echo json_encode(['ok' => false, 'message' => 'You already have a pending/approved application.']);
  exit;
}
$dupe->close();

// Insert row
$ins = $connection->prepare(
  "INSERT INTO tbl_creator_applications
   (user_id, educ_attainment, school, field_of_expertise, proof_file_url, status)
   VALUES (?, ?, ?, ?, ?, 'pending')"
);
$ins->bind_param("issss", $user_id, $educ, $school, $expertise, $publicUrl);

if ($ins->execute()) {
  echo json_encode(['ok' => true, 'message' => 'Application submitted.', 'application_id' => $ins->insert_id]);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Database error while saving application.']);
}
$ins->close();
