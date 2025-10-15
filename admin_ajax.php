<?php
session_start();
require_once 'config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Custom error logging function
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
}

// Admin action logging function
function logAdminAction($connection, $adminUserId, $action, $targetType, $targetId) {
    try {
        $stmt = $connection->prepare("INSERT INTO tbl_admin_audit_logs (user_id, action, target_type, target_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("issi", $adminUserId, $action, $targetType, $targetId);
            $stmt->execute();
        }
    } catch (Exception $e) {
        logError('Failed to log admin action', ['error' => $e->getMessage()]);
    }
}

// Error handling with logging
set_error_handler(function($severity, $message, $file, $line) {
    $errorMsg = "Error: $message in $file on line $line";
    logError($errorMsg);
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Log incoming request for debugging
    logError('Admin AJAX Request', [
        'action' => $_GET['action'] ?? $_POST['action'] ?? 'none',
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST,
        'get_data' => $_GET
    ]);

// Check if user is logged in and has Kanri role
if (!isset($_SESSION['sUser'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['sUser'];
$stmt = $connection->prepare("SELECT user_id, role FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $adminUserID = $row['user_id'];
    if ($row['role'] !== 'Kanri') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';



switch ($action) {
    // User Management
    case 'get_user':
        try {
            $userId = $_GET['user_id'] ?? 0;
            
            logError('Getting user', ['user_id' => $userId]);
            
            $stmt = $connection->prepare("SELECT * FROM tbl_user WHERE user_id = ?");
            if (!$stmt) throw new Exception('Prepare failed: ' . $connection->error);
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                logError('User retrieved successfully', ['user_id' => $userId]);
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                logError('User not found', ['user_id' => $userId]);
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } catch (Exception $e) {
            logError('Error getting user', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Error getting user: ' . $e->getMessage()]);
        }
        break;

    case 'create_user':
        try {
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $username = $_POST['username'] ?? '';
            $email = $_POST['email_address'] ?? '';
            $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
            $role = $_POST['role'] ?? 'Gakusei';
            $subscriptionType = $_POST['subscription_type'] ?? 'Free';
            $gakucoins = $_POST['gakucoins'] ?? 0;
            $isVerified = isset($_POST['is_verified']) ? 1 : 0;

            logError('Creating user', ['username' => $username, 'email' => $email]);

            $stmt = $connection->prepare("INSERT INTO tbl_user (first_name, last_name, username, email_address, pass, role, subscription_type, gakucoins, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $connection->error);
            }
            
            $stmt->bind_param("sssssssii", $firstName, $lastName, $username, $email, $password, $role, $subscriptionType, $gakucoins, $isVerified);
            
            if ($stmt->execute()) {
                $newUserId = $connection->insert_id;
                logAdminAction($connection, $adminUserID, "Created user: $username", 'user', $newUserId);
                logError('User created successfully', ['user_id' => $newUserId]);
                echo json_encode(['success' => true, 'message' => 'User created successfully', 'user_id' => $newUserId]);
            } else {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
        } catch (Exception $e) {
            logError('Error creating user', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()]);
        }
        break;

    case 'update_user':
        try {
            $userId = $_POST['user_id'] ?? 0;
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $username = $_POST['username'] ?? '';
            $email = $_POST['email_address'] ?? '';
            $role = $_POST['role'] ?? 'Gakusei';
            $subscriptionType = $_POST['subscription_type'] ?? 'Free';
            $gakucoins = $_POST['gakucoins'] ?? 0;
            $isVerified = isset($_POST['is_verified']) ? 1 : 0;

            logError('Updating user', ['user_id' => $userId, 'username' => $username]);

            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $connection->prepare("UPDATE tbl_user SET first_name=?, last_name=?, username=?, email_address=?, pass=?, role=?, subscription_type=?, gakucoins=?, is_verified=? WHERE user_id=?");
                if (!$stmt) throw new Exception('Prepare failed: ' . $connection->error);
                $stmt->bind_param("ssssssssii", $firstName, $lastName, $username, $email, $password, $role, $subscriptionType, $gakucoins, $isVerified, $userId);
            } else {
                $stmt = $connection->prepare("UPDATE tbl_user SET first_name=?, last_name=?, username=?, email_address=?, role=?, subscription_type=?, gakucoins=?, is_verified=? WHERE user_id=?");
                if (!$stmt) throw new Exception('Prepare failed: ' . $connection->error);
                $stmt->bind_param("sssssssii", $firstName, $lastName, $username, $email, $role, $subscriptionType, $gakucoins, $isVerified, $userId);
            }

            if ($stmt->execute()) {
                logAdminAction($connection, $adminUserID, "Updated user: $username", 'user', $userId);
                logError('User updated successfully', ['user_id' => $userId]);
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
        } catch (Exception $e) {
            logError('Error updating user', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
        }
        break;

    case 'delete_user':
        try {
            $userId = $_POST['user_id'] ?? 0;
            
            logError('Deleting user', ['user_id' => $userId]);
            
            // Get username for logging
            $stmt = $connection->prepare("SELECT username FROM tbl_user WHERE user_id = ?");
            if (!$stmt) throw new Exception('Prepare failed: ' . $connection->error);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $stmt = $connection->prepare("DELETE FROM tbl_user WHERE user_id = ?");
            if (!$stmt) throw new Exception('Prepare failed: ' . $connection->error);
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                logAdminAction($connection, $adminUserID, "Deleted user: " . $user['username'], 'user', $userId);
                logError('User deleted successfully', ['user_id' => $userId, 'username' => $user['username']]);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
        } catch (Exception $e) {
            logError('Error deleting user', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
        }
        break;

    // Lesson Management
    case 'get_lesson':
        $lessonId = $_GET['lesson_id'];
        $stmt = $connection->prepare("SELECT * FROM tbl_lesson WHERE lesson_id = ?");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($lesson = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'lesson' => $lesson]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lesson not found']);
        }
        break;

    case 'create_lesson':
        try {
            $title = $_POST['title'] ?? '';
            $shortDesc = $_POST['short_desc'] ?? '';
            $longDesc = $_POST['long_desc'] ?? '';
            $duration = $_POST['duration'] ?? '00:30:00';
            $topicId = $_POST['topic_id'] ?? 1;
            $difficultyLevel = $_POST['difficulty_level'] ?? 'Beginner';
            $isPrivate = isset($_POST['is_private']) ? 1 : 0;
            $authorId = !empty($_POST['author_id']) ? $_POST['author_id'] : null;

            // Convert duration text to TIME format
            if (strpos($duration, 'minutes') !== false) {
                $minutes = (int) filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
                $duration = sprintf('%02d:%02d:00', 0, $minutes);
            } elseif (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $duration)) {
                $duration = '00:30:00'; // Default 30 minutes
            }

            // Validate topic_id exists
            $checkStmt = $connection->prepare("SELECT topic_id FROM tbl_topic WHERE topic_id = ?");
            $checkStmt->bind_param("i", $topicId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            if (!$result->fetch_assoc()) {
                throw new Exception('Referenced topic does not exist');
            }

            // Validate author_id if provided
            if ($authorId) {
                $checkStmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE user_id = ?");
                $checkStmt->bind_param("i", $authorId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                if (!$result->fetch_assoc()) {
                    $authorId = null; // Set to null if user doesn't exist
                }
            }

            logError('Creating lesson', ['title' => $title, 'duration' => $duration, 'author_id' => $authorId]);

            $stmt = $connection->prepare("INSERT INTO tbl_lesson (title, short_desc, long_desc, duration, topic_id, difficulty_level, is_private, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $connection->error);
            
            $stmt->bind_param("ssssisii", $title, $shortDesc, $longDesc, $duration, $topicId, $difficultyLevel, $isPrivate, $authorId);
            
            if ($stmt->execute()) {
                $newLessonId = $connection->insert_id;
                logAdminAction($connection, $adminUserID, "Created lesson: $title", 'lesson', $newLessonId);
                logError('Lesson created successfully', ['lesson_id' => $newLessonId]);
                echo json_encode(['success' => true, 'message' => 'Lesson created successfully', 'lesson_id' => $newLessonId]);
            } else {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
        } catch (Exception $e) {
            logError('Error creating lesson', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Error creating lesson: ' . $e->getMessage()]);
        }
        break;

    case 'update_lesson':
        $lessonId = $_POST['lesson_id'];
        $title = $_POST['title'];
        $shortDesc = $_POST['short_desc'];
        $longDesc = $_POST['long_desc'];
        $duration = $_POST['duration'];
        $topicId = $_POST['topic_id'];
        $difficultyLevel = $_POST['difficulty_level'];
        $isPrivate = isset($_POST['is_private']) ? 1 : 0;
        $authorId = !empty($_POST['author_id']) ? $_POST['author_id'] : null;

        $stmt = $connection->prepare("UPDATE tbl_lesson SET title=?, short_desc=?, long_desc=?, duration=?, topic_id=?, difficulty_level=?, is_private=?, author_id=? WHERE lesson_id=?");
        $stmt->bind_param("ssssisiii", $title, $shortDesc, $longDesc, $duration, $topicId, $difficultyLevel, $isPrivate, $authorId, $lessonId);

        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Updated lesson: $title", 'lesson', $lessonId);
            echo json_encode(['success' => true, 'message' => 'Lesson updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating lesson: ' . $connection->error]);
        }
        break;

    case 'delete_lesson':
        $lessonId = $_POST['lesson_id'];
        
        // Get lesson title for logging
        $stmt = $connection->prepare("SELECT title FROM tbl_lesson WHERE lesson_id = ?");
        $stmt->bind_param("i", $lessonId);
        $stmt->execute();
        $result = $stmt->get_result();
        $lesson = $result->fetch_assoc();
        
        $stmt = $connection->prepare("DELETE FROM tbl_lesson WHERE lesson_id = ?");
        $stmt->bind_param("i", $lessonId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Deleted lesson: " . $lesson['title'], 'lesson', $lessonId);
            echo json_encode(['success' => true, 'message' => 'Lesson deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting lesson: ' . $connection->error]);
        }
        break;

    // Quiz Management
    case 'get_quiz':
        $quizId = $_GET['quiz_id'];
        $stmt = $connection->prepare("SELECT * FROM tbl_quizzes WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($quiz = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'quiz' => $quiz]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Quiz not found']);
        }
        break;

    case 'create_quiz':
        try {
            $title = $_POST['title'] ?? '';
            $lessonId = !empty($_POST['lesson_id']) ? $_POST['lesson_id'] : null;
            $isAiGenerated = isset($_POST['is_ai_generated']) ? 1 : 0;
            $authorId = !empty($_POST['author_id']) ? $_POST['author_id'] : null;

            // Validate author_id if provided
            if ($authorId) {
                $checkStmt = $connection->prepare("SELECT user_id FROM tbl_user WHERE user_id = ?");
                $checkStmt->bind_param("i", $authorId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                if (!$result->fetch_assoc()) {
                    $authorId = null; // Set to null if user doesn't exist
                }
            }

            logError('Creating quiz', ['title' => $title, 'lesson_id' => $lessonId, 'author_id' => $authorId]);

            // If lesson_id is provided, verify it exists
            if ($lessonId) {
                $checkStmt = $connection->prepare("SELECT lesson_id FROM tbl_lesson WHERE lesson_id = ?");
                $checkStmt->bind_param("i", $lessonId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                if (!$result->fetch_assoc()) {
                    throw new Exception('Referenced lesson does not exist');
                }
            }

            $stmt = $connection->prepare("INSERT INTO tbl_quizzes (title, lesson_id, is_ai_generated, author_id) VALUES (?, ?, ?, ?)");
            if (!$stmt) throw new Exception('Prepare failed: ' . $connection->error);
            
            $stmt->bind_param("siii", $title, $lessonId, $isAiGenerated, $authorId);
            
            if ($stmt->execute()) {
                $newQuizId = $connection->insert_id;
                logAdminAction($connection, $adminUserID, "Created quiz: $title", 'quiz', $newQuizId);
                logError('Quiz created successfully', ['quiz_id' => $newQuizId]);
                echo json_encode(['success' => true, 'message' => 'Quiz created successfully', 'quiz_id' => $newQuizId]);
            } else {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
        } catch (Exception $e) {
            logError('Error creating quiz', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Error creating quiz: ' . $e->getMessage()]);
        }
        break;

    case 'update_quiz':
        $quizId = $_POST['quiz_id'];
        $title = $_POST['title'];
        $lessonId = !empty($_POST['lesson_id']) ? $_POST['lesson_id'] : null;
        $isAiGenerated = isset($_POST['is_ai_generated']) ? 1 : 0;
        $authorId = !empty($_POST['author_id']) ? $_POST['author_id'] : null;

        $stmt = $connection->prepare("UPDATE tbl_quizzes SET title=?, lesson_id=?, is_ai_generated=?, author_id=? WHERE quiz_id=?");
        $stmt->bind_param("siiii", $title, $lessonId, $isAiGenerated, $authorId, $quizId);

        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Updated quiz: $title", 'quiz', $quizId);
            echo json_encode(['success' => true, 'message' => 'Quiz updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating quiz: ' . $connection->error]);
        }
        break;

    case 'delete_quiz':
        $quizId = $_POST['quiz_id'];
        
        // Get quiz title for logging
        $stmt = $connection->prepare("SELECT title FROM tbl_quizzes WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $result = $stmt->get_result();
        $quiz = $result->fetch_assoc();
        
        $stmt = $connection->prepare("DELETE FROM tbl_quizzes WHERE quiz_id = ?");
        $stmt->bind_param("i", $quizId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Deleted quiz: " . ($quiz['title'] ?: 'Untitled'), 'quiz', $quizId);
            echo json_encode(['success' => true, 'message' => 'Quiz deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting quiz: ' . $connection->error]);
        }
        break;

    // Creator Management
    case 'approve_application':
        $applicationId = $_POST['application_id'];
        
        // Get application details
        $stmt = $connection->prepare("SELECT user_id FROM tbl_creator_applications WHERE application_id = ?");
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        
        if ($application) {
            // Update application status
            $stmt = $connection->prepare("UPDATE tbl_creator_applications SET status = 'approved' WHERE application_id = ?");
            $stmt->bind_param("i", $applicationId);
            $stmt->execute();
            
            // Update user role to Gakusensei
            $stmt = $connection->prepare("UPDATE tbl_user SET role = 'Gakusensei' WHERE user_id = ?");
            $stmt->bind_param("i", $application['user_id']);
            $stmt->execute();
            
            logAdminAction($connection, $adminUserID, "Approved creator application", 'user', $application['user_id']);
            echo json_encode(['success' => true, 'message' => 'Application approved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Application not found']);
        }
        break;

    case 'reject_application':
        $applicationId = $_POST['application_id'];
        
        $stmt = $connection->prepare("UPDATE tbl_creator_applications SET status = 'rejected' WHERE application_id = ?");
        $stmt->bind_param("i", $applicationId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Rejected creator application", 'user', $applicationId);
            echo json_encode(['success' => true, 'message' => 'Application rejected successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error rejecting application: ' . $connection->error]);
        }
        break;

    case 'process_payout':
        $payoutId = $_POST['payout_id'];
        
        $stmt = $connection->prepare("UPDATE tbl_creator_payouts SET last_payout = NOW() WHERE payout_id = ?");
        $stmt->bind_param("i", $payoutId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Processed payout", 'user', $payoutId);
            echo json_encode(['success' => true, 'message' => 'Payout processed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error processing payout: ' . $connection->error]);
        }
        break;

    // Shop Management
    case 'get_shop_item':
        $itemId = $_GET['item_id'];
        $stmt = $connection->prepare("SELECT * FROM tbl_shop_items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($item = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
        break;

    case 'create_shop_item':
        $itemType = $_POST['item_type'];
        $itemName = $_POST['item_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $energyRestore = !empty($_POST['energy_restore']) ? $_POST['energy_restore'] : null;
        $imageUrl = $_POST['image_url'];

        $stmt = $connection->prepare("INSERT INTO tbl_shop_items (item_type, item_name, description, price, energy_restore, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $itemType, $itemName, $description, $price, $energyRestore, $imageUrl);
        
        if ($stmt->execute()) {
            $newItemId = $connection->insert_id;
            logAdminAction($connection, $adminUserID, "Created shop item: $itemName", 'item', $newItemId);
            echo json_encode(['success' => true, 'message' => 'Shop item created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating shop item: ' . $connection->error]);
        }
        break;

    case 'update_shop_item':
        $itemId = $_POST['item_id'];
        $itemType = $_POST['item_type'];
        $itemName = $_POST['item_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $energyRestore = !empty($_POST['energy_restore']) ? $_POST['energy_restore'] : null;
        $imageUrl = $_POST['image_url'];

        $stmt = $connection->prepare("UPDATE tbl_shop_items SET item_type=?, item_name=?, description=?, price=?, energy_restore=?, image_url=? WHERE item_id=?");
        $stmt->bind_param("ssssisi", $itemType, $itemName, $description, $price, $energyRestore, $imageUrl, $itemId);

        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Updated shop item: $itemName", 'item', $itemId);
            echo json_encode(['success' => true, 'message' => 'Shop item updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating shop item: ' . $connection->error]);
        }
        break;

    case 'delete_shop_item':
        $itemId = $_POST['item_id'];
        
        // Get item name for logging
        $stmt = $connection->prepare("SELECT item_name FROM tbl_shop_items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        $stmt = $connection->prepare("DELETE FROM tbl_shop_items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Deleted shop item: " . $item['item_name'], 'item', $itemId);
            echo json_encode(['success' => true, 'message' => 'Shop item deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting shop item: ' . $connection->error]);
        }
        break;

    case 'grant_item':
        $itemId = $_POST['item_id'];
        $userId = $_POST['user_id'];
        
        // Check if item exists
        $stmt = $connection->prepare("SELECT item_type, item_name FROM tbl_shop_items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            break;
        }
        
        // Grant item to user
        $stmt = $connection->prepare("INSERT INTO tbl_user_items (user_id, item_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $itemId);
        
        if ($stmt->execute()) {
            // If it's food, also add to user_foods
            if ($item['item_type'] === 'food') {
                $stmt = $connection->prepare("INSERT INTO tbl_user_foods (user_id, item_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
                $stmt->bind_param("ii", $userId, $itemId);
                $stmt->execute();
            }
            
            // If it's accessory, also add to user_accessories
            if ($item['item_type'] === 'accessory') {
                $stmt = $connection->prepare("INSERT INTO tbl_user_accessories (user_id, item_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id");
                $stmt->bind_param("ii", $userId, $itemId);
                $stmt->execute();
            }
            
            logAdminAction($connection, $adminUserID, "Granted item: " . $item['item_name'] . " to user ID: $userId", 'item', $itemId);
            echo json_encode(['success' => true, 'message' => 'Item granted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error granting item: ' . $connection->error]);
        }
        break;

    // System Management
    case 'get_topic':
        $topicId = $_GET['topic_id'];
        $stmt = $connection->prepare("SELECT * FROM tbl_topic WHERE topic_id = ?");
        $stmt->bind_param("i", $topicId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($topic = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'topic' => $topic]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Topic not found']);
        }
        break;

    case 'create_topic':
        $topicName = $_POST['topic_name'];
        $topicIcon = $_POST['topic_icon'];

        $stmt = $connection->prepare("INSERT INTO tbl_topic (topic_name, topic_icon) VALUES (?, ?)");
        $stmt->bind_param("ss", $topicName, $topicIcon);
        
        if ($stmt->execute()) {
            $newTopicId = $connection->insert_id;
            logAdminAction($connection, $adminUserID, "Created topic: $topicName", 'item', $newTopicId);
            echo json_encode(['success' => true, 'message' => 'Topic created successfully', 'topic_id' => $newTopicId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating topic: ' . $connection->error]);
        }
        break;

    case 'update_topic':
        $topicId = $_POST['topic_id'];
        $topicName = $_POST['topic_name'];
        $topicIcon = $_POST['topic_icon'];

        $stmt = $connection->prepare("UPDATE tbl_topic SET topic_name=?, topic_icon=? WHERE topic_id=?");
        $stmt->bind_param("ssi", $topicName, $topicIcon, $topicId);

        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Updated topic: $topicName", 'item', $topicId);
            echo json_encode(['success' => true, 'message' => 'Topic updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating topic: ' . $connection->error]);
        }
        break;

    case 'delete_topic':
        $topicId = $_POST['topic_id'];
        
        // Get topic name for logging
        $stmt = $connection->prepare("SELECT topic_name FROM tbl_topic WHERE topic_id = ?");
        $stmt->bind_param("i", $topicId);
        $stmt->execute();
        $result = $stmt->get_result();
        $topic = $result->fetch_assoc();
        
        $stmt = $connection->prepare("DELETE FROM tbl_topic WHERE topic_id = ?");
        $stmt->bind_param("i", $topicId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Deleted topic: " . $topic['topic_name'], 'item', $topicId);
            echo json_encode(['success' => true, 'message' => 'Topic deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting topic: ' . $connection->error]);
        }
        break;

    case 'delete_feedback':
        $feedbackId = $_POST['feedback_id'];
        
        $stmt = $connection->prepare("DELETE FROM tbl_feedback WHERE feedback_id = ?");
        $stmt->bind_param("i", $feedbackId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Deleted feedback", 'item', $feedbackId);
            echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting feedback: ' . $connection->error]);
        }
        break;

    case 'approve_verification':
        $indexId = $_POST['index_id'];
        
        // Get pending verification data
        $stmt = $connection->prepare("SELECT * FROM tbl_pending_verif WHERE index_id = ?");
        $stmt->bind_param("i", $indexId);
        $stmt->execute();
        $result = $stmt->get_result();
        $pending = $result->fetch_assoc();
        
        if ($pending) {
            // Create user account
            $stmt = $connection->prepare("INSERT INTO tbl_user (first_name, last_name, username, email_address, pass, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssss", $pending['first_name'], $pending['last_name'], $pending['username'], $pending['email_address'], $pending['pass']);
            
            if ($stmt->execute()) {
                $newUserId = $connection->insert_id;
                
                // Delete from pending verification
                $stmt = $connection->prepare("DELETE FROM tbl_pending_verif WHERE index_id = ?");
                $stmt->bind_param("i", $indexId);
                $stmt->execute();
                
                logAdminAction($connection, $adminUserID, "Approved user verification: " . $pending['username'], 'user', $newUserId);
                echo json_encode(['success' => true, 'message' => 'Verification approved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating user account: ' . $connection->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Pending verification not found']);
        }
        break;

    case 'reject_verification':
        $indexId = $_POST['index_id'];
        
        $stmt = $connection->prepare("DELETE FROM tbl_pending_verif WHERE index_id = ?");
        $stmt->bind_param("i", $indexId);
        
        if ($stmt->execute()) {
            logAdminAction($connection, $adminUserID, "Rejected user verification", 'user', $indexId);
            echo json_encode(['success' => true, 'message' => 'Verification rejected successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error rejecting verification: ' . $connection->error]);
        }
        break;

    // Table refresh endpoints
    case 'get_users_table':
        $users_result = $connection->query("
            SELECT user_id, username, email_address, first_name, last_name, role, 
                   subscription_type, gakucoins, is_verified, created_at 
            FROM tbl_user 
            ORDER BY created_at DESC
            LIMIT 50
        ");
        
        $html = '';
        while ($user = $users_result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $user['user_id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($user['username']) . '</td>';
            $html .= '<td>' . htmlspecialchars($user['email_address']) . '</td>';
            $html .= '<td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
            $html .= '<td><span class="badge badge-' . strtolower($user['role']) . '">' . $user['role'] . '</span></td>';
            $html .= '<td><span class="badge badge-' . strtolower($user['subscription_type']) . '">' . $user['subscription_type'] . '</span></td>';
            $html .= '<td>' . number_format($user['gakucoins']) . '</td>';
            $html .= '<td><span class="badge badge-' . ($user['is_verified'] ? 'success' : 'warning') . '">' . ($user['is_verified'] ? 'Verified' : 'Pending') . '</span></td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editUser(' . $user['user_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteUser(' . $user['user_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '<button class="btn-action btn-view" onclick="viewUserDetails(' . $user['user_id'] . ')"><i class="bi bi-eye"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search endpoints
    case 'search_users':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE username LIKE ? OR email_address LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
        }
        
        $sql = "SELECT user_id, username, email_address, first_name, last_name, role, subscription_type, gakucoins, is_verified, created_at FROM tbl_user $whereClause ORDER BY user_id ASC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('ssss', $query, $query, $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($user = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $user['user_id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($user['username']) . '</td>';
            $html .= '<td>' . htmlspecialchars($user['email_address']) . '</td>';
            $html .= '<td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
            $html .= '<td><span class="badge badge-' . strtolower($user['role']) . '">' . $user['role'] . '</span></td>';
            $html .= '<td><span class="badge badge-' . strtolower($user['subscription_type']) . '">' . $user['subscription_type'] . '</span></td>';
            $html .= '<td>' . number_format($user['gakucoins']) . '</td>';
            $html .= '<td><span class="badge badge-' . ($user['is_verified'] ? 'success' : 'warning') . '">' . ($user['is_verified'] ? 'Verified' : 'Pending') . '</span></td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editUser(' . $user['user_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteUser(' . $user['user_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '<button class="btn-action btn-view" onclick="viewUserDetails(' . $user['user_id'] . ')"><i class="bi bi-eye"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;
        
    case 'search_lessons':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE l.title LIKE ? OR l.short_desc LIKE ?";
        }
        
        $sql = "SELECT l.*, t.topic_name, u.username as author_name, COUNT(e.user_id) as enrollment_count FROM tbl_lesson l LEFT JOIN tbl_topic t ON l.topic_id = t.topic_id LEFT JOIN tbl_user u ON l.author_id = u.user_id LEFT JOIN tbl_user_enrollments e ON l.lesson_id = e.lesson_id $whereClause GROUP BY l.lesson_id ORDER BY l.lesson_id ASC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('ss', $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($lesson = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $lesson['lesson_id'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($lesson['title']) . '</strong><br><small>' . htmlspecialchars(substr($lesson['short_desc'], 0, 50)) . '...</small></td>';
            $html .= '<td>' . htmlspecialchars($lesson['topic_name']) . '</td>';
            $html .= '<td>' . ($lesson['author_name'] ?: 'System') . '</td>';
            $html .= '<td><span class="badge badge-' . strtolower($lesson['difficulty_level']) . '">' . $lesson['difficulty_level'] . '</span></td>';
            $html .= '<td>' . $lesson['enrollment_count'] . '</td>';
            $html .= '<td><span class="badge badge-' . ($lesson['is_private'] ? 'warning' : 'success') . '">' . ($lesson['is_private'] ? 'Private' : 'Public') . '</span></td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editLesson(' . $lesson['lesson_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteLesson(' . $lesson['lesson_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '<button class="btn-action btn-view" onclick="viewLessonDetails(' . $lesson['lesson_id'] . ')"><i class="bi bi-eye"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;
        
    case 'search_quizs':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE q.title LIKE ?";
        }
        
        $sql = "SELECT q.*, l.title as lesson_title, u.username as author_name FROM tbl_quizzes q LEFT JOIN tbl_lesson l ON q.lesson_id = l.lesson_id LEFT JOIN tbl_user u ON q.author_id = u.user_id $whereClause ORDER BY q.quiz_id ASC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('s', $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($quiz = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $quiz['quiz_id'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($quiz['title']) . '</strong></td>';
            $html .= '<td>' . ($quiz['lesson_title'] ? htmlspecialchars($quiz['lesson_title']) : 'Standalone') . '</td>';
            $html .= '<td>' . ($quiz['author_name'] ?: 'System') . '</td>';
            $html .= '<td><span class="badge badge-' . ($quiz['is_ai_generated'] ? 'info' : 'primary') . '">' . ($quiz['is_ai_generated'] ? 'AI Generated' : 'Manual') . '</span></td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editQuiz(' . $quiz['quiz_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteQuiz(' . $quiz['quiz_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '<button class="btn-action btn-view" onclick="manageQuestions(' . $quiz['quiz_id'] . ')"><i class="bi bi-list-ul"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;
        
    case 'get_users_count':
        $query = $_GET['query'] ?? '';
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE username LIKE ? OR email_address LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
        }
        
        $sql = "SELECT COUNT(*) as total FROM tbl_user $whereClause";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('ssss', $query, $query, $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];
        
        echo json_encode(['total' => $count]);
        break;
        
    case 'get_lessons_count':
        $query = $_GET['query'] ?? '';
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE l.title LIKE ? OR l.short_desc LIKE ?";
        }
        
        $sql = "SELECT COUNT(*) as total FROM tbl_lesson l $whereClause";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('ss', $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];
        
        echo json_encode(['total' => $count]);
        break;
        
    case 'get_quizzes_count':
        $query = $_GET['query'] ?? '';
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE q.title LIKE ?";
        }
        
        $sql = "SELECT COUNT(*) as total FROM tbl_quizzes q $whereClause";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('s', $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['total'];
        
        echo json_encode(['total' => $count]);
        break;
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE q.title LIKE ?";
        }
        
        $sql = "SELECT q.quiz_id, q.title, q.is_ai_generated, q.created_at, l.title as lesson_title, COUNT(DISTINCT qn.question_id) as question_count, COUNT(DISTINCT qa.attempt_id) as attempt_count, AVG(qa.score) as avg_score FROM tbl_quizzes q LEFT JOIN tbl_lesson l ON q.lesson_id = l.lesson_id LEFT JOIN tbl_questions qn ON q.quiz_id = qn.quiz_id LEFT JOIN tbl_user_quiz_attempts qa ON q.quiz_id = qa.quiz_id $whereClause GROUP BY q.quiz_id ORDER BY q.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('s', $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($quiz = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $quiz['quiz_id'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($quiz['title'] ?: 'Untitled Quiz') . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($quiz['lesson_title'] ?: 'Standalone') . '</td>';
            $html .= '<td>' . $quiz['question_count'] . '</td>';
            $html .= '<td><span class="badge badge-' . ($quiz['is_ai_generated'] ? 'info' : 'primary') . '">' . ($quiz['is_ai_generated'] ? 'AI Generated' : 'Manual') . '</span></td>';
            $html .= '<td>' . $quiz['attempt_count'] . '</td>';
            $html .= '<td>' . ($quiz['avg_score'] ? number_format($quiz['avg_score'], 1) : 'N/A') . '</td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editQuiz(' . $quiz['quiz_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteQuiz(' . $quiz['quiz_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search shop items
    case 'search_shop_items':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE item_name LIKE ? OR description LIKE ? OR item_type LIKE ?";
        }
        
        $sql = "SELECT * FROM tbl_shop_items $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('sss', $query, $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($item = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $item['item_id'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($item['item_name']) . '</strong><br><small>' . htmlspecialchars(substr($item['description'], 0, 50)) . '...</small></td>';
            $html .= '<td><span class="badge badge-' . strtolower($item['item_type']) . '">' . ucfirst($item['item_type']) . '</span></td>';
            $html .= '<td>' . number_format($item['price']) . '</td>';
            $html .= '<td>' . ($item['energy_restore'] ?: 'N/A') . '</td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editShopItem(' . $item['item_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteShopItem(' . $item['item_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '<button class="btn-action btn-success" onclick="grantItemToUser(' . $item['item_id'] . ')"><i class="bi bi-gift"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search creator applications
    case 'search_creator_applications':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE u.username LIKE ? OR u.email_address LIKE ? OR ca.portfolio_url LIKE ?";
        }
        
        $sql = "SELECT ca.*, u.username, u.email_address FROM tbl_creator_applications ca JOIN tbl_user u ON ca.user_id = u.user_id $whereClause ORDER BY ca.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('sss', $query, $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($app = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $app['application_id'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($app['username']) . '</strong><br><small>' . htmlspecialchars($app['email_address']) . '</small></td>';
            $html .= '<td>' . htmlspecialchars(substr($app['portfolio_url'], 0, 30)) . '...</td>';
            $html .= '<td><span class="badge badge-' . strtolower($app['status']) . '">' . ucfirst($app['status']) . '</span></td>';
            $html .= '<td>' . date('M j, Y', strtotime($app['created_at'])) . '</td>';
            $html .= '<td><div class="action-buttons">';
            if ($app['status'] === 'pending') {
                $html .= '<button class="btn-action btn-success" onclick="approveApplication(' . $app['application_id'] . ')"><i class="bi bi-check"></i></button>';
                $html .= '<button class="btn-action btn-danger" onclick="rejectApplication(' . $app['application_id'] . ')"><i class="bi bi-x"></i></button>';
            }
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search feedback
    case 'search_feedback':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE f.feedback_text LIKE ? OR u.username LIKE ?";
        }
        
        $sql = "SELECT f.*, u.username FROM tbl_feedback f LEFT JOIN tbl_user u ON f.user_id = u.user_id $whereClause ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('ss', $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($feedback = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $feedback['feedback_id'] . '</td>';
            $html .= '<td>' . ($feedback['username'] ?: 'Anonymous') . '</td>';
            $html .= '<td>' . htmlspecialchars(substr($feedback['feedback_text'], 0, 100)) . '...</td>';
            $html .= '<td>' . date('M j, Y', strtotime($feedback['created_at'])) . '</td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-delete" onclick="deleteFeedback(' . $feedback['feedback_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search pending verifications
    case 'search_pending_verifications':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE username LIKE ? OR email_address LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
        }
        
        $sql = "SELECT * FROM tbl_pending_verif $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('ssss', $query, $query, $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($pending = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $pending['index_id'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($pending['username']) . '</strong><br><small>' . htmlspecialchars($pending['email_address']) . '</small></td>';
            $html .= '<td>' . htmlspecialchars($pending['first_name'] . ' ' . $pending['last_name']) . '</td>';
            $html .= '<td>' . date('M j, Y', strtotime($pending['created_at'])) . '</td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-success" onclick="approveVerification(' . $pending['index_id'] . ')"><i class="bi bi-check"></i></button>';
            $html .= '<button class="btn-action btn-danger" onclick="rejectVerification(' . $pending['index_id'] . ')"><i class="bi bi-x"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search topics
    case 'search_topics':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE topic_name LIKE ?";
        }
        
        $sql = "SELECT t.*, COUNT(l.lesson_id) as lesson_count FROM tbl_topic t LEFT JOIN tbl_lesson l ON t.topic_id = l.topic_id $whereClause GROUP BY t.topic_id ORDER BY t.topic_name LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('s', $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($topic = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $topic['topic_id'] . '</td>';
            $html .= '<td><i class="' . htmlspecialchars($topic['topic_icon']) . '"></i> ' . htmlspecialchars($topic['topic_name']) . '</td>';
            $html .= '<td>' . $topic['lesson_count'] . '</td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editTopic(' . $topic['topic_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteTopic(' . $topic['topic_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Get shop items for dropdown
    case 'get_shop_items':
        $result = $connection->query("SELECT item_id, item_name, price, item_type FROM tbl_shop_items ORDER BY item_name");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode(['success' => true, 'items' => $items]);
        break;

    // Get users for dropdown with proper format
    case 'get_users_for_dropdown':
        $result = $connection->query("SELECT user_id, username, email_address FROM tbl_user ORDER BY username LIMIT 100");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);
        break;

    // Adjust gakucoins
    case 'adjust_gakucoins':
        try {
            $userId = $_POST['user_id'] ?? 0;
            $action = $_POST['coin_action'] ?? 'set';
            $amount = (int)($_POST['amount'] ?? 0);
            
            if ($amount < 0) {
                throw new Exception('Amount cannot be negative');
            }
            
            // Get current user data
            $stmt = $connection->prepare("SELECT username, gakucoins FROM tbl_user WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $currentCoins = $user['gakucoins'];
            $newCoins = 0;
            
            switch ($action) {
                case 'set':
                    $newCoins = $amount;
                    break;
                case 'add':
                    $newCoins = $currentCoins + $amount;
                    break;
                case 'remove':
                    $newCoins = max(0, $currentCoins - $amount);
                    break;
                default:
                    throw new Exception('Invalid action');
            }
            
            // Update user's gakucoins
            $stmt = $connection->prepare("UPDATE tbl_user SET gakucoins = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $newCoins, $userId);
            
            if ($stmt->execute()) {
                logAdminAction($connection, $adminUserID, "Adjusted gakucoins for " . $user['username'] . " from $currentCoins to $newCoins", 'user', $userId);
                echo json_encode(['success' => true, 'message' => "Gakucoins updated successfully. New balance: " . number_format($newCoins)]);
            } else {
                throw new Exception('Failed to update gakucoins');
            }
        } catch (Exception $e) {
            logError('Error adjusting gakucoins', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => 'Error adjusting gakucoins: ' . $e->getMessage()]);
        }
        break;

    // Search creators (creator applications)
    case 'search_creators':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE u.username LIKE ? OR u.email_address LIKE ? OR ca.portfolio_url LIKE ?";
        }
        
        $sql = "SELECT ca.*, u.username, u.email_address FROM tbl_creator_applications ca JOIN tbl_user u ON ca.user_id = u.user_id $whereClause ORDER BY ca.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('sss', $query, $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($app = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $app['application_id'] . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($app['username']) . '</strong><br><small>' . htmlspecialchars($app['email_address']) . '</small></td>';
            $html .= '<td>' . htmlspecialchars($app['educ_attainment']) . '</td>';
            $html .= '<td>' . htmlspecialchars($app['school']) . '</td>';
            $html .= '<td>' . htmlspecialchars($app['field_of_expertise']) . '</td>';
            $html .= '<td><span class="badge badge-' . strtolower($app['status']) . '">' . ucfirst($app['status']) . '</span></td>';
            $html .= '<td><div class="action-buttons">';
            if ($app['status'] === 'pending') {
                $html .= '<button class="btn-action btn-success" onclick="approveApplication(' . $app['application_id'] . ')"><i class="bi bi-check"></i></button>';
                $html .= '<button class="btn-action btn-danger" onclick="rejectApplication(' . $app['application_id'] . ')"><i class="bi bi-x"></i></button>';
            }
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search shops (shop items)
    case 'search_shops':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE item_name LIKE ? OR description LIKE ? OR item_type LIKE ?";
        }
        
        $sql = "SELECT si.*, COUNT(ui.user_item_id) as sales_count FROM tbl_shop_items si LEFT JOIN tbl_user_items ui ON si.item_id = ui.item_id $whereClause GROUP BY si.item_id ORDER BY si.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('sss', $query, $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($item = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . $item['item_id'] . '</td>';
            $html .= '<td><span style="font-size: 24px;">' . $item['image_url'] . '</span></td>';
            $html .= '<td><span class="badge badge-' . $item['item_type'] . '">' . ucfirst($item['item_type']) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($item['item_name']) . '</td>';
            $html .= '<td>' . $item['price'] . ' coins</td>';
            $html .= '<td>' . $item['sales_count'] . '</td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-edit" onclick="editShopItem(' . $item['item_id'] . ')"><i class="bi bi-pencil"></i></button>';
            $html .= '<button class="btn-action btn-delete" onclick="deleteShopItem(' . $item['item_id'] . ')"><i class="bi bi-trash"></i></button>';
            $html .= '<button class="btn-action btn-primary" onclick="grantItemToUser(' . $item['item_id'] . ')"><i class="bi bi-gift"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Search inventory
    case 'search_inventory':
        $query = $_GET['query'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        if (!empty($query)) {
            $query = '%' . $query . '%';
            $whereClause = "WHERE u.username LIKE ? OR u.email_address LIKE ?";
        }
        
        $sql = "SELECT u.user_id, u.username, u.email_address, u.gakucoins,
                       COUNT(DISTINCT uf.user_food_id) as food_count,
                       COUNT(DISTINCT ua.user_accessory_id) as accessory_count,
                       up.energy_level
                FROM tbl_user u
                LEFT JOIN tbl_user_foods uf ON u.user_id = uf.user_id
                LEFT JOIN tbl_user_accessories ua ON u.user_id = ua.user_id
                LEFT JOIN tbl_user_pet up ON u.user_id = up.user_id
                $whereClause
                GROUP BY u.user_id
                ORDER BY u.user_id ASC
                LIMIT $limit OFFSET $offset";
        $stmt = $connection->prepare($sql);
        
        if (!empty($query)) {
            $stmt->bind_param('ss', $query, $query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($user = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($user['username']) . '</strong><br><small>Email: ' . htmlspecialchars($user['email_address']) . '</small></td>';
            $html .= '<td>' . number_format($user['gakucoins']) . '</td>';
            $html .= '<td>' . $user['food_count'] . '</td>';
            $html .= '<td>' . $user['accessory_count'] . '</td>';
            $html .= '<td>';
            if ($user['energy_level'] !== null) {
                $html .= '<div class="energy-bar"><div class="energy-fill" style="width: ' . $user['energy_level'] . '%"></div><span class="energy-text">' . $user['energy_level'] . '%</span></div>';
            } else {
                $html .= '<span class="text-muted">No pet</span>';
            }
            $html .= '</td>';
            $html .= '<td><div class="action-buttons">';
            $html .= '<button class="btn-action btn-success" onclick="grantItemsToUser(' . $user['user_id'] . ')"><i class="bi bi-gift"></i></button>';
            $html .= '<button class="btn-action btn-primary" onclick="showGakucoinModal(' . $user['user_id'] . ', \'' . htmlspecialchars($user['username']) . '\', ' . $user['gakucoins'] . ')"><i class="bi bi-coin"></i></button>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
        
        echo $html;
        break;

    // Chart Data
    case 'get_chart_data':
        $userGrowth = [];
        $topLessons = [];
        
        // User growth data
        $result = $connection->query("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM tbl_user 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            GROUP BY DATE(created_at) 
            ORDER BY date
        ");
        while ($row = $result->fetch_assoc()) {
            $userGrowth['labels'][] = $row['date'];
            $userGrowth['data'][] = $row['count'];
        }
        
        // Top lessons data
        $result = $connection->query("
            SELECT l.title, COUNT(e.user_id) as enrollment_count 
            FROM tbl_lesson l 
            LEFT JOIN tbl_user_enrollments e ON l.lesson_id = e.lesson_id 
            GROUP BY l.lesson_id 
            ORDER BY enrollment_count DESC 
            LIMIT 10
        ");
        while ($row = $result->fetch_assoc()) {
            $topLessons['labels'][] = substr($row['title'], 0, 20) . '...';
            $topLessons['data'][] = $row['enrollment_count'];
        }
        
        echo json_encode([
            'user_growth' => $userGrowth,
            'top_lessons' => $topLessons
        ]);
        break;

    default:
        $action = $_GET['action'] ?? $_POST['action'] ?? 'none';
        logError('Invalid action requested', ['action' => $action]);
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

} catch (Exception $e) {
    $errorDetails = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    logError('Exception caught in admin_ajax.php', $errorDetails);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'debug' => $errorDetails
    ]);
}
?>