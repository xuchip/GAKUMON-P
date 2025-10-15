<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = '127.0.0.1';
    $user = 'root';
    $password = 'admin';
    $database = 'db_gakumon';

    try {
        $connection = new mysqli($host, $user, $password, $database);
    }
    catch (mysqli_sql_exception $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit();
    }

    if (!function_exists('logAdminAction')) {
        function logAdminAction($connection, $userId, $action, $targetType, $targetId) {
            $stmt = $connection->prepare("
                INSERT INTO tbl_admin_audit_logs (user_id, action, target_type, target_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("issi", $userId, $action, $targetType, $targetId);
            $ok = $stmt->execute();

            if (!$ok) {
                error_log("Audit log failed: " . $stmt->error);
            } else {
                error_log("Audit log success for user_id {$userId}");
            }

            $stmt->close();
        }
    }


?>
