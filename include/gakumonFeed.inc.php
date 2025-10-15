<?php
// include/gakumonFeed.inc.php
session_start();
header('Content-Type: application/json');

// ===== DIAGNOSTICS ON (comment out in production) =====
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// ======================================================

require_once __DIR__ . '/../config/config.php';

$debug = ['step' => 'start'];

try {
  if (empty($_SESSION['sUser'])) {
    echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit;
  }

  // Resolve user_id
  $username = $_SESSION['sUser'];
  $stmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE username = ? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->bind_result($userId);
  $foundUser = $stmt->fetch();
  $stmt->close();
  if (!$foundUser || !$userId) {
    echo json_encode(['ok'=>false,'error'=>'User not found']); exit;
  }
  $debug['user_id'] = (int)$userId;

  // Input
  $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
  if ($itemId <= 0) { echo json_encode(['ok'=>false,'error'=>'Invalid item']); exit; }
  $debug['item_id'] = $itemId;

  // Look up item meta (schema uses `item_type` and `name`)
  $stmt = $connection->prepare("
    SELECT item_type, COALESCE(energy_restore,0)
    FROM tbl_shop_items
    WHERE item_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $itemId);
  $stmt->execute();
  $stmt->bind_result($itemType, $energyRestore);
  $hasItem = $stmt->fetch();
  $stmt->close();

  if (!$hasItem) { echo json_encode(['ok'=>false,'error'=>'Item not found']); exit; }
  if (strtolower($itemType) !== 'food') {
    echo json_encode(['ok'=>false,'error'=>'Item is not food']); exit;
  }
  $energyRestore = (int)$energyRestore;
  $debug['energy_restore'] = $energyRestore;

  $connection->begin_transaction();

  // Check user owns at least 1
  $stmt = $connection->prepare("
    SELECT user_food_id, quantity
    FROM tbl_user_foods
    WHERE user_id = ? AND item_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("ii", $userId, $itemId);
  $stmt->execute();
  $stmt->bind_result($userFoodId, $qty);
  $hasFood = $stmt->fetch();
  $stmt->close();
  $debug['has_food'] = (bool)$hasFood;
  $debug['qty_before'] = (int)$qty;

  if (!$hasFood || $qty <= 0) {
    $connection->rollback();
    echo json_encode(['ok'=>false,'error'=>'No stock','debug'=>$debug]); exit;
  }

  // Decrement (or delete when 0)
  if ((int)$qty === 1) {
    $stmt = $connection->prepare("DELETE FROM tbl_user_foods WHERE user_food_id = ?");
    $stmt->bind_param("i", $userFoodId);
    $stmt->execute();
    $affectedFoods = $stmt->affected_rows;
    $stmt->close();
    $debug['foods_update'] = "DELETE";
  } else {
    $stmt = $connection->prepare("UPDATE tbl_user_foods SET quantity = quantity - 1 WHERE user_food_id = ?");
    $stmt->bind_param("i", $userFoodId);
    $stmt->execute();
    $affectedFoods = $stmt->affected_rows;
    $stmt->close();
    $debug['foods_update'] = "UPDATE -1";
  }
  $debug['foods_rows_affected'] = (int)$affectedFoods;

  // Ensure there is a pet row (newest by created_at)
  $stmt = $connection->prepare("
    SELECT user_pet_id, energy_level
    FROM tbl_user_pet
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($userPetId, $currEnergy);
  $hasPet = $stmt->fetch();
  $stmt->close();
  $debug['has_pet'] = (bool)$hasPet;

  if (!$hasPet) {
    // Create a default pet row if somehow missing
    $stmt = $connection->prepare("
      INSERT INTO tbl_user_pet (user_id, pet_id, custom_name, energy_level, created_at, last_energy_update)
      VALUES (?, (SELECT MIN(pet_id) FROM tbl_pet), NULL, 0, NOW(), NOW())
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    $debug['created_pet'] = true;
  }

  // Update latest pet row; use JOIN to target exactly one row
  $stmt = $connection->prepare("
    UPDATE tbl_user_pet p
    JOIN (
      SELECT user_pet_id
      FROM tbl_user_pet
      WHERE user_id = ?
      ORDER BY created_at DESC
      LIMIT 1
    ) x ON x.user_pet_id = p.user_pet_id
    SET p.energy_level = LEAST(100, COALESCE(p.energy_level,0) + ?),
        p.last_energy_update = CURRENT_TIMESTAMP
  ");
  $stmt->bind_param("ii", $userId, $energyRestore);
  $stmt->execute();
  $affectedPet = $stmt->affected_rows;
  $stmt->close();
  $debug['pet_rows_affected'] = (int)$affectedPet;

  if ($affectedPet === 0) {
    // Something is off — roll back with details
    $connection->rollback();
    echo json_encode(['ok'=>false,'error'=>'Energy not updated','debug'=>$debug]); exit;
  }

  // Read back the fresh energy + remaining qty
  $stmt = $connection->prepare("
    SELECT energy_level
    FROM tbl_user_pet
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($energyLevel);
  $stmt->fetch();
  $stmt->close();

  $connection->commit();

  echo json_encode([
    'ok'          => true,
    'energy'      => (int)$energyLevel,
    'remaining'   => max(0, ((int)$qty) - 1),
    'energy_gain' => $energyRestore,
    'debug'       => $debug // remove in production if you prefer
  ]);
  exit;

} catch (Throwable $e) {
  if ($connection && $connection->errno === 0) {
    // best effort rollback
    try { $connection->rollback(); } catch (Throwable $ignored) {}
  }
  echo json_encode(['ok'=>false,'error'=>'Server exception','exception'=>$e->getMessage(),'debug'=>$debug]); exit;
}
