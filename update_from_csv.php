<?php
// update_from_csv.php
// This script downloads the latest CSV from a remote server,
// parses it, and updates the database with the latest subscription info.

// Database connection
require_once 'config/config.php';
// --- Step 1: Download remote CSV ---
$remote_csv_url = "https://gakumon.folded.cloud/subscriptions.csv"; // <-- CHANGE THIS
$local_csv_path = __DIR__ . "/subscriptions_temp.csv";

// Download CSV file
$file_contents = file_get_contents($remote_csv_url);
if ($file_contents === false) {
    die("Failed to download remote CSV file.");
}

// Save locally
file_put_contents($local_csv_path, $file_contents);

// --- Step 2: Read the latest entry ---
$rows = array_map('str_getcsv', file($local_csv_path));
$headers = array_shift($rows); // remove header row
$last_row = end($rows);

if (!$last_row || count($last_row) < 3) {
    die("CSV appears invalid or empty.");
}

// Assuming CSV columns: username, subscription_type, subscription_expiry
list($username, $subscription_type, $subscription_expiry) = $last_row;

// --- Step 3: Update database ---
$stmt = $connection->prepare("
    UPDATE tbl_user 
    SET subscription_type = ?, subscription_expires = ? 
    WHERE username = ?
");
$stmt->bind_param("sss", $subscription_type, $subscription_expiry, $username);

if ($stmt->execute()) {
    echo "Updated $username → $subscription_type (expires $subscription_expiry)";
} else {
    echo "Database update failed: " . $stmt->error;
}

$stmt->close();
$connection->close();

// Optional: Delete the temp file
unlink($local_csv_path);
?>