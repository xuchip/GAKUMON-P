<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/config.php'; // must set $connection (mysqli)

function back(string $msg): void {
  $_SESSION['success_message'] = $msg;          // homepage can read & display this
  header('Location: ../homepage.php');  // optional anchor to jump back to section
  exit;
}

/* 1) Auth → user_id */
if (!isset($_SESSION['sUser'])) back('Please log in.');
$username = $_SESSION['sUser'];
$stmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE username=?");
if (!$stmt) back('DB error (prepare user).');
$stmt->bind_param('s', $username);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$u) back('User not found.');
$user_id = (int)$u['user_id'];

/* 2) Read + validate POST (must match your form names) */
$first  = trim($_POST['firstName'] ?? '');
$last   = trim($_POST['lastName'] ?? '');
$bank   = trim($_POST['bankName'] ?? '');                 // e.g., bpi|bdo|...|other
$acct   = trim($_POST['accountNumber'] ?? '');
$type   = trim($_POST['accountType'] ?? '');              // savings|checking|current
$mobile = trim($_POST['mobileNumber'] ?? '');
$terms  = isset($_POST['termsAgreement']);
$otherBank = ($bank === 'other') ? trim($_POST['otherBank'] ?? '') : null;

if ($first===''||$last===''||$bank===''||$acct===''||$type===''||$mobile===''||!$terms) {
  back('Please complete all required fields and accept the terms.');
}
if ($bank === 'other' && $otherBank === '') back('Please specify the Other bank.');
if (!in_array($type, ['savings','checking','current'], true)) back('Invalid account type.');

/* 3) Optional QR upload (<=2MB, image only) */
$qr_url = null; $qr_name = null;
if (!empty($_FILES['qrCode']['name'])) {
  if ($_FILES['qrCode']['error'] !== UPLOAD_ERR_OK) back('QR upload failed (code '.$_FILES['qrCode']['error'].').');
  $ext = strtolower(pathinfo($_FILES['qrCode']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','gif'], true)) back('QR must be jpg, jpeg, png, or gif.');
  if ($_FILES['qrCode']['size'] > 2*1024*1024) back('QR image is larger than 2MB.');

  $dir = __DIR__ . '/IMG/BankQR';
  if (!is_dir($dir) && !@mkdir($dir, 0775, true)) back('Cannot create upload folder.');
  if (!is_writable($dir)) back('Upload folder not writable.');

  $fname = sprintf('qr_u%s_%s.%s', $user_id, bin2hex(random_bytes(6)), $ext);
  if (!move_uploaded_file($_FILES['qrCode']['tmp_name'], $dir.'/'.$fname)) back('Failed to store QR image.');
  $qr_url  = 'IMG/BankQR/' . $fname;          // web path to store
  $qr_name = $_FILES['qrCode']['name'];       // original filename (optional)
}

/* 4) Upsert into tbl_gakusensei_bank_info (one profile per user) */
$sql = "
  INSERT INTO tbl_gakusensei_bank_info
    (user_id, account_first_name, account_last_name, bank_code, other_bank_name,
     account_number, account_type, mobile_number, qr_code_url, qr_code_original_name)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
     account_first_name=VALUES(account_first_name),
     account_last_name =VALUES(account_last_name),
     bank_code         =VALUES(bank_code),
     other_bank_name   =VALUES(other_bank_name),
     account_number    =VALUES(account_number),
     account_type      =VALUES(account_type),
     mobile_number     =VALUES(mobile_number),
     qr_code_url       =VALUES(qr_code_url),
     qr_code_original_name=VALUES(qr_code_original_name)
";
$stmt = $connection->prepare($sql);
if (!$stmt) back('DB error (prepare save).');
$stmt->bind_param(
  'isssssssss',
  $user_id, $first, $last, $bank, $otherBank, $acct, $type, $mobile, $qr_url, $qr_name
);
if (!$stmt->execute()) { $e = $stmt->error; $stmt->close(); back('DB error (execute): '.$e); }
$stmt->close();

back('Bank info saved successfully!');
