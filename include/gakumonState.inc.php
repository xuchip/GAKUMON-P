<?php
// include/gakumonState.inc.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['sUser'])) {
  echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit;
}

// Resolve user_id
$userId = null;
$stmt = $connection->prepare("SELECT user_id, gakucoins FROM tbl_user WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['sUser']);
$stmt->execute();
$stmt->bind_result($userId, $coins);
if (!$stmt->fetch()) { $stmt->close(); echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }
$stmt->close();

// Pet (latest)
$pet = null;
$stmt = $connection->prepare("
  SELECT up.energy_level, up.created_at, COALESCE(p.pet_name,'') AS pet_name, COALESCE(p.image_url,'') AS image_url
  FROM tbl_user_pet up
  LEFT JOIN tbl_pet p ON p.pet_id = up.pet_id
  WHERE up.user_id = ?
  ORDER BY up.created_at DESC
  LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($energy, $createdAt, $petName, $imgUrl);
if ($stmt->fetch()) {
  $ageDays = (new DateTime())->diff(new DateTime($createdAt))->days;
  $pet = [
    'name'      => $petName ?: 'Pet',
    'type'      => $petName ?: 'Pet',
    'age'       => (int)$ageDays,
    'energy'    => (int)$energy,
    'maxEnergy' => 100,
    'imageUrl'  => $imgUrl,
  ];
}
$stmt->close();

// Inventory (foods, accessories, wallpapers) joined to shop for metadata
$normalizeType = function($t){
  $t = strtolower(trim((string)$t));
  if ($t === 'accessory') return 'accessories';
  return $t;
};

$inventory = [];

// Foods
$stmt = $connection->prepare("
  SELECT f.item_id, f.quantity, si.item_name, si.item_type, si.price, si.image_url, si.energy_restore
  FROM tbl_user_foods f
  JOIN tbl_shop_items si ON si.item_id = f.item_id
  WHERE f.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
  $inventory[] = [
    'id'             => (int)$r['item_id'],
    'name'           => $r['item_name'],
    'type'           => $normalizeType($r['item_type']),
    'price'          => (int)$r['price'],
    'icon'           => $r['image_url'],
    'energy_restore' => (int)($r['energy_restore'] ?? 0),
    'owned'          => (int)$r['quantity'],
    'equipped'       => false,
  ];
}
$stmt->close();

// Accessories (own once)
$stmt = $connection->prepare("
  SELECT a.item_id, a.is_equipped, si.item_name, si.item_type, si.price, si.image_url
  FROM tbl_user_accessories a
  JOIN tbl_shop_items si ON si.item_id = a.item_id
  WHERE a.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
  $inventory[] = [
    'id'             => (int)$r['item_id'],
    'name'           => $r['item_name'],
    'type'           => $normalizeType($r['item_type']),
    'price'          => (int)$r['price'],
    'icon'           => $r['image_url'],
    'energy_restore' => null,
    'owned'          => 1,
    'equipped'       => (bool)$r['is_equipped'],
  ];
}
$stmt->close();

echo json_encode([
  'ok'       => true,
  'currency' => (int)$coins,
  'pet'      => $pet ?: ['name'=>'MOHI','type'=>'CHRIX','age'=>0,'energy'=>100,'maxEnergy'=>100,'imageUrl'=>null],
  'inventory'=> $inventory
]);
