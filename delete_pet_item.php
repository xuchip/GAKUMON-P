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

if (!isset($_POST['item_id']) || empty($_POST['item_id'])) {
    echo json_encode(['success' => false, 'message' => 'Item ID is required.']);
    exit;
}

$item_id = intval($_POST['item_id']);

try {
    // Delete the item
    $stmt = $connection->prepare("DELETE FROM tbl_shop_items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        // ✅ AUDIT: record Kanri deleting a pet item (minimal insert)
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
                    "Deleted a pet item (Item ID: {$auditItemId})",    // action
                    'item',                                           // target_type enum
                    $auditItemId                                      // target_id
                );
            }
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully.']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete item.']);
    }
    
    
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$connection->close();
?>