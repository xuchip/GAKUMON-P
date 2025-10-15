<?php
// api/gakumon_buy.php
session_start();
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['sUser'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit;
}

// Resolve user
$stmt = $connection->prepare("SELECT user_id, gakucoins FROM tbl_user WHERE username=?");
$stmt->bind_param("s", $_SESSION['sUser']);
$stmt->execute();
$stmt->bind_result($userId, $coins);
$stmt->fetch();
$stmt->close();

if (!$userId) { echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }

// Input
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
if ($itemId <= 0) { echo json_encode(['ok'=>false,'error'=>'Invalid item']); exit; }

// Item details
$stmt = $connection->prepare("SELECT item_type, price FROM tbl_shop_items WHERE item_id=?");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$stmt->bind_result($itemType, $price);
if (!$stmt->fetch()) { $stmt->close(); echo json_encode(['ok'=>false,'error'=>'Item not found']); exit; }
$stmt->close();

if ($coins < $price) { echo json_encode(['ok'=>false,'error'=>'Insufficient Gakucoins']); exit; }

$connection->begin_transaction();

try {
  // 1) Atomic coin deduction (prevents negatives)
  $stmt = $connection->prepare("
    UPDATE tbl_user
       SET gakucoins = gakucoins - ?
     WHERE user_id = ? AND gakucoins >= ?
  ");
  $stmt->bind_param("iii", $price, $userId, $price);
  $stmt->execute();
  if ($stmt->affected_rows === 0) {
    $stmt->close();
    $connection->rollback();
    echo json_encode(['ok'=>false,'error'=>'Insufficient Gakucoins']); exit;
  }
  $stmt->close();

  // 2) Purchase history (always)
  $stmt = $connection->prepare("INSERT INTO tbl_user_items (user_id, item_id) VALUES (?, ?)");
  $stmt->bind_param("ii", $userId, $itemId);
  $stmt->execute();
  $stmt->close();

  // 3) Ownership by type (UPSERTS)
  if ($itemType === 'food') {
    // insert first time, otherwise +1
    $stmt = $connection->prepare("
      INSERT INTO tbl_user_foods (user_id, item_id, quantity)
      VALUES (?, ?, 1)
      ON DUPLICATE KEY UPDATE quantity = quantity + 1
    ");
    $stmt->bind_param("ii", $userId, $itemId);
    $stmt->execute();
    $stmt->close();

  } elseif ($itemType === 'accessory') {
    // insert only once; ignore if already owned
    $stmt = $connection->prepare("
      INSERT INTO tbl_user_accessories (user_id, item_id, is_equipped)
      VALUES (?, ?, 0)
      ON DUPLICATE KEY UPDATE user_id = user_id
    ");
    $stmt->bind_param("ii", $userId, $itemId);
    $stmt->execute();
    $stmt->close();

  }

  // 4) Return fresh coin balance
  $stmt = $connection->prepare("SELECT gakucoins FROM tbl_user WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($newCoins);
  $stmt->fetch();
  $stmt->close();

  $connection->commit();

  echo json_encode(['ok'=>true,'currency'=>(int)$newCoins]); exit;

} catch (Throwable $e) {
  $connection->rollback();
  echo json_encode(['ok'=>false,'error'=>'Server error']); exit;
}

