<?php
// Database connection test
require_once 'config/config.php';

header('Content-Type: application/json');

try {
    // Test basic connection
    if (!$connection) {
        throw new Exception('Database connection failed');
    }
    
    // Test if connection is alive
    if (!$connection->ping()) {
        throw new Exception('Database connection is not alive');
    }
    
    // Test basic query
    $result = $connection->query("SELECT COUNT(*) as count FROM tbl_user");
    if (!$result) {
        throw new Exception('Query failed: ' . $connection->error);
    }
    
    $row = $result->fetch_assoc();
    
    // Test table structure
    $tables = [];
    $result = $connection->query("SHOW TABLES");
    while ($row_table = $result->fetch_array()) {
        $tables[] = $row_table[0];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'user_count' => $row['count'],
        'tables' => $tables,
        'server_info' => $connection->server_info,
        'host_info' => $connection->host_info
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>