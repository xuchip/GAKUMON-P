<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in and has Kanri role
if (!isset($_SESSION['sUser']) || $_SESSION['sRole'] !== 'Kanri') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'single') {
        $userId = $input['user_id'] ?? 0;
        processSinglePayout($userId);
    } elseif ($action === 'all') {
        processAllPayouts();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function processSinglePayout($userId) {
    global $connection;
    
    try {
        $connection->begin_transaction();
        
        // Get current earned_amount for payout
        $stmt = $connection->prepare("
            SELECT COALESCE(SUM(earned_amount), 0) as payout_amount 
            FROM tbl_creator_earnings 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $payoutAmount = $row['payout_amount'];
        
        if ($payoutAmount > 0) {
            // Add earned_amount to total_earnings and reset earned_amount to 0
            $stmt = $connection->prepare("
                UPDATE tbl_creator_earnings 
                SET total_earnings = total_earnings + earned_amount,
                    earned_amount = 0 
                WHERE user_id = ?
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            // Get bank details from user or existing payout record
            $stmt = $connection->prepare("
                SELECT bank_name, account_number, qr_code_url 
                FROM tbl_creator_payouts 
                WHERE user_id = ? 
                ORDER BY last_payout DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $bankDetails = $result->fetch_assoc();
                $bankName = $bankDetails['bank_name'];
                $accountNumber = $bankDetails['account_number'];
                $qrCodeUrl = $bankDetails['qr_code_url'];
            } else {
                // If no existing payout record, use default/empty values
                $bankName = 'Not set';
                $accountNumber = 'Not set';
                $qrCodeUrl = null;
            }
            
            // Insert payout record with the actual payout amount
            $stmt = $connection->prepare("
                INSERT INTO tbl_creator_payouts (user_id, payout_amount, bank_name, account_number, qr_code_url, last_payout) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("idsss", $userId, $payoutAmount, $bankName, $accountNumber, $qrCodeUrl);
            $stmt->execute();
            
            $connection->commit();
            echo json_encode(['success' => true, 'message' => 'Payout processed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No earnings to payout']);
        }
        
    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function processAllPayouts() {
    global $connection;
    
    try {
        $connection->begin_transaction();
        
        // Get all Gakusensei with earned_amount above 0
        $query = "
            SELECT 
                u.user_id, 
                COALESCE(SUM(ce.earned_amount), 0) as payout_amount
            FROM tbl_user u
            LEFT JOIN tbl_creator_earnings ce ON u.user_id = ce.user_id
            WHERE u.role = 'Gakusensei'
            GROUP BY u.user_id
            HAVING payout_amount > 0
        ";
        
        $result = $connection->query($query);
        $processedCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $userId = $row['user_id'];
            $payoutAmount = $row['payout_amount'];
            
            // Add earned_amount to total_earnings and reset earned_amount to 0
            $stmt = $connection->prepare("
                UPDATE tbl_creator_earnings 
                SET total_earnings = total_earnings + earned_amount,
                    earned_amount = 0 
                WHERE user_id = ?
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            // Get bank details from user or existing payout record
            $stmt = $connection->prepare("
                SELECT bank_name, account_number, qr_code_url 
                FROM tbl_creator_payouts 
                WHERE user_id = ? 
                ORDER BY last_payout DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $bankResult = $stmt->get_result();
            
            if ($bankResult->num_rows > 0) {
                $bankDetails = $bankResult->fetch_assoc();
                $bankName = $bankDetails['bank_name'];
                $accountNumber = $bankDetails['account_number'];
                $qrCodeUrl = $bankDetails['qr_code_url'];
            } else {
                // If no existing payout record, use default/empty values
                $bankName = 'Not set';
                $accountNumber = 'Not set';
                $qrCodeUrl = null;
            }
            
            // Insert payout record with the actual payout amount
            $stmt = $connection->prepare("
                INSERT INTO tbl_creator_payouts (user_id, payout_amount, bank_name, account_number, qr_code_url, last_payout) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("idsss", $userId, $payoutAmount, $bankName, $accountNumber, $qrCodeUrl);
            $stmt->execute();
            
            $processedCount++;
        }
        
        $connection->commit();
        echo json_encode(['success' => true, 'message' => "Successfully processed $processedCount payouts"]);
        
    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>