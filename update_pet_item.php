<?php
session_start();
require_once 'config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['sUser'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get and validate form data
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : null;
$item_name = isset($_POST['item_name']) ? trim($_POST['item_name']) : null;
$image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : null;
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$price = isset($_POST['price']) ? intval($_POST['price']) : null;
$hp_value = isset($_POST['hp_value']) ? intval($_POST['hp_value']) : 0;
$category = isset($_POST['category']) ? trim($_POST['category']) : null;

// Validate required fields
if (!$item_id || !$item_name || !$image_url || !$description || !$price || !$category) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Validate category
$allowed_categories = ['food', 'accessory', 'wallpaper'];
if (!in_array($category, $allowed_categories)) {
    echo json_encode(['success' => false, 'message' => 'Invalid category selected.']);
    exit;
}

// Set energy_restore to NULL for non-food items
$energy_restore = ($category === 'food') ? $hp_value : NULL;

try {
    // Update item information
    $update_stmt = $connection->prepare("UPDATE tbl_shop_items SET item_name = ?, image_url = ?, description = ?, price = ?, energy_restore = ?, item_type = ? WHERE item_id = ?");
    $update_stmt->bind_param("sssiisi", $item_name, $image_url, $description, $price, $energy_restore, $category, $item_id);
    
    if ($update_stmt->execute()) {
        // ✅ AUDIT: record Kanri editing a pet item (minimal insert)
        if (isset($_SESSION['sRole'], $_SESSION['sUserID'])
            && $_SESSION['sRole'] === 'Kanri'
            && function_exists('logAdminAction')) {

            // Capture the actual item id used by THIS file
            $auditItemId = 0;
            if (isset($item_id) && (int)$item_id > 0)                 { $auditItemId = (int)$item_id; }
            elseif (isset($pet_item_id) && (int)$pet_item_id > 0)     { $auditItemId = (int)$pet_item_id; }
            elseif (isset($id) && (int)$id > 0)                       { $auditItemId = (int)$id; }
            elseif (isset($_POST['item_id']) && (int)$_POST['item_id'] > 0)           { $auditItemId = (int)$_POST['item_id']; }
            elseif (isset($_POST['pet_item_id']) && (int)$_POST['pet_item_id'] > 0)   { $auditItemId = (int)$_POST['pet_item_id']; }
            elseif (isset($_POST['id']) && (int)$_POST['id'] > 0)                     { $auditItemId = (int)$_POST['id']; }

            if ($auditItemId > 0) {
                logAdminAction(
                    $connection,
                    (int) $_SESSION['sUserID'],                       // your session key
                    "Edited a pet item (Item ID: {$auditItemId})",    // action
                    'item',                                           // target_type enum
                    $auditItemId                                      // target_id
                );
            }
        }
        $update_stmt->close();
        echo json_encode(['success' => true, 'message' => 'Item updated successfully.']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update item.']);
    }
    
    
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$connection->close();
?>