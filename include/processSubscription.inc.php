<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_POST['user_id'] ?? '';
    $amount = $_POST['amount'] ?? '150';
    $payment_method = $_POST['paymentMethod'] ?? '';
    
    // Validate required fields
    if (empty($user_id) || empty($payment_method)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        // Start transaction
        $connection->begin_transaction();
        
        // Update user subscription to Premium
        $stmt = $connection->prepare("UPDATE tbl_user SET subscription_type = 'Premium', updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update subscription: " . $stmt->error);
        }
        
        // Insert payment record (you might want to create a payments table)
        // For now, we'll just update the user subscription
        
        // Commit transaction
        $connection->commit();
        
        // Update session variable if current user
        if (isset($_SESSION['sUser']) && $_SESSION['sUserID'] == $user_id) {
            $_SESSION['sSubscription'] = 'Premium';
        }
        
        echo json_encode(['success' => true, 'message' => 'Subscription activated successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $stmt->close();
    $connection->close();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>