<?php
// webhook.php (no DB — debug logs written to webhook_new_logs.log)
date_default_timezone_set('UTC');
$logfile = __DIR__ . '/webhook_new_logs.log';

header('Content-Type: application/json');

// helper: append structured log line
function write_log(array $data) {
    global $logfile;
    $entry = [
        'ts' => date('c'),
        'data' => $data
    ];
    // append as one JSON-per-line
    file_put_contents($logfile, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// read raw input
$raw = file_get_contents('php://input');
write_log(['event' => 'raw_input', 'raw_length' => strlen($raw), 'raw_preview' => substr($raw, 0, 200)]);
$incoming = json_decode($raw, true);

// If top-level JSON invalid, respond 400 and log
if (!is_array($incoming)) {
    write_log(['event' => 'invalid_json', 'json_error' => json_last_error_msg(), 'raw' => $raw]);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid JSON']);
    exit;
}

// helper: recursively find first value by key name
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

// get payload (handle wrapper with body_raw which may be escaped JSON)
$payload = $incoming;
if (isset($incoming['body_raw'])) {
    $bodyRaw = $incoming['body_raw'];
    write_log(['event' => 'found_body_raw', 'len' => strlen($bodyRaw), 'preview' => substr($bodyRaw, 0, 200)]);
    $decoded = json_decode($bodyRaw, true);

    // try a few fallbacks if direct decode fails
    if (!is_array($decoded)) {
        // attempt stripslashes
        $try2 = stripslashes($bodyRaw);
        $decoded = json_decode($try2, true);
    }
    if (!is_array($decoded)) {
        // trim surrounding quotes
        $try3 = trim($bodyRaw, "\"'");
        $decoded = json_decode($try3, true);
    }
    if (!is_array($decoded)) {
        // replace escaped newlines/tabs and try
        $try4 = str_replace(["\\n","\\r","\\t"], ["\n","\r","\t"], $bodyRaw);
        $decoded = json_decode($try4, true);
    }
    write_log(['event' => 'body_raw_decode_attempt', 'decoded_ok' => is_array($decoded), 'json_error' => json_last_error_msg()]);
    if (is_array($decoded)) {
        $payload = $decoded;
    } else {
        // keep wrapper as payload but log we couldn't decode inner JSON
        write_log(['event' => 'body_raw_decode_failed', 'body_raw' => substr($bodyRaw, 0, 1000)]);
    }
}

// Extract description and paid_at
$description = $payload['data']['attributes']['data']['attributes']['description'] ?? null;
if ($description === null) $description = find_first_by_key($payload, 'description');

$paidAtCandidate = find_first_by_key($payload, 'paid_at');
$paidAt = $paidAtCandidate !== null ? (int)$paidAtCandidate : null;

write_log([
    'event' => 'extracted_fields',
    'description_present' => $description !== null,
    'paid_at_present' => $paidAt !== null,
    'description_preview' => $description === null ? null : substr($description, 0, 200),
    'paid_at' => $paidAt
]);

// validation
if (empty($description) || !is_string($description) || empty($paidAt)) {
    $resp = ['ok' => false, 'error' => 'missing description or paid_at', 'description' => $description, 'paid_at' => $paidAt];
    write_log(['event' => 'validation_failed', 'response' => $resp, 'payload_preview' => $payload]);
    http_response_code(422);
    echo json_encode($resp);
    exit;
}

// extract username after ">>>"
if (preg_match('/>>> *([A-Za-z0-9_\-]+)/', $description, $m)) {
    $username = $m[1];
} else {
    $resp = ['ok' => false, 'error' => 'username pattern not found in description', 'description' => $description];
    write_log(['event' => 'username_not_found', 'description' => $description]);
    http_response_code(422);
    echo json_encode($resp);
    exit;
}

// compute expiry = paid_at + 30 days
$expiryUnix = $paidAt + 30 * 86400;
$expiryDatetime = date('Y-m-d H:i:s', $expiryUnix);

// Build result (no DB) and log it
$result = [
    'ok' => true,
    'username' => $username,
    'previous_subscription' => 'N/A (no DB)',
    'new_subscription' => 'Premium',
    'subscription_expires' => $expiryDatetime,
    'paid_at' => $paidAt,
    'expiry_unix' => $expiryUnix,
    'payload_preview' => $payload
];

write_log(['event' => 'subscription_promote', 'result' => $result]);

// respond 200
http_response_code(200);
echo json_encode($result);
exit;

