<?php
// include/lessonObjectives.inc.php
declare(strict_types=1);

header('Content-Type: application/json');

// 1) DB include — adjust to your real bootstrap if different
require_once __DIR__ . '/../config/config.php';  

if (!isset($connection) || !($connection instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'NO_DB_CONNECTION']);
  exit;
}
$connection->set_charset('utf8mb4');

// 2) Accept lesson_id OR id (and optional title fallback)
$lessonId = 0;
if (isset($_GET['lesson_id'])) {
  $lessonId = (int) $_GET['lesson_id'];
} elseif (isset($_GET['id'])) {
  $lessonId = (int) $_GET['id'];
}

if ($lessonId <= 0 && !empty($_GET['title'])) {
  $stmt = $connection->prepare('SELECT lesson_id FROM tbl_lesson WHERE title = ? LIMIT 1');
  $stmt->bind_param('s', $_GET['title']);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) $lessonId = (int)$row['lesson_id'];
  $stmt->close();
}

if ($lessonId < 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'BAD_LESSON_ID']);
  exit;
}

// 3) Fetch objectives
$q = $connection->prepare("
  SELECT objective_text, objective_order
  FROM tbl_lesson_objectives
  WHERE lesson_id = ?
  ORDER BY objective_order ASC, objective_id ASC
");
$q->bind_param('i', $lessonId);
$q->execute();
$res = $q->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
  $out[] = [
    'objective_text'  => (string)$row['objective_text'],
    'objective_order' => (int)$row['objective_order'],
  ];
}
$q->close();

// 4) Return JSON
echo json_encode(['ok' => true, 'lesson_id' => $lessonId, 'objectives' => $out], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
