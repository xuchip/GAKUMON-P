<?php
// webhook_csv.php
date_default_timezone_set('UTC');
$csvFile = __DIR__ . '/subscriptions.csv';

header('Content-Type: application/json');

// Read raw body
$raw = file_get_contents('php://input');
$incoming = json_decode($raw, true);

// If wrapped under "body_raw", decode again
if (isset($incoming['body_raw'])) {
    $inner = json_decode($incoming['body_raw'], true);
    if (is_array($inner)) $incoming = $inner;
}

// Helper recursive key finder
function find_first_by_key($arr, string $key) {
    if (!is_array($arr)) return null;
    foreach ($arr as $k => $v) {
        if ($k === $key) return $v;
        if (is_array($v)) {
            $found = find_first_by_key($v, $key);
            if ($found !== null) return $found;
        }
    }
    return null;
}

// Extract description and paid_at
$description = $incoming['data']['attributes']['data']['attributes']['description'] ?? null;
if ($description === null) $description = find_first_by_key($incoming, 'description');
$paidAt = find_first_by_key($incoming, 'paid_at');

if (empty($description) || empty($paidAt)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'missing description or paid_at']);
    exit;
}

// Extract username
if (!preg_match('/>>> *([A-Za-z0-9_\-]+)/', $description, $m)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'username not found']);
    exit;
}
$username = $m[1];

// Compute expiry = paid_at + 30 days
$expiryUnix = (int)$paidAt + 30 * 86400;
$expiryDatetime = date('Y-m-d H:i:s', $expiryUnix);
$now = date('Y-m-d H:i:s');

// Append to CSV
$fp = fopen($csvFile, 'a');
fputcsv($fp, [$username, 'Premium', $expiryDatetime, $now]);
fclose($fp);

// Respond success
echo json_encode([
    'ok' => true,
    'username' => $username,
    'subscription_type' => 'Premium',
    'subscription_expires' => $expiryDatetime,
    'saved_to' => basename($csvFile)
]);

