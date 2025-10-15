<?php
// retrieveFileContent.php

// 1. SET THESE VARIABLES TO WHAT YOU NEED
$lesson_id = isset($lesson_id) && $lesson_id ? (int)$lesson_id : (int)($_GET['lesson_id'] ?? 0);
$file_id   = isset($file_id)   && $file_id   ? (int)$file_id   : (int)($_GET['file_id'] ?? 0);

// PDF Parser - make sure you ran: composer require smalot/pdfparser
require_once __DIR__ . '/vendor/autoload.php';

// 2. Database configuration
require_once __DIR__ . '/config/config.php';
$connection->set_charset('utf8mb4');

// 3. Function to get file details where BOTH lesson_id AND file_id match
function getFileDetails($fileId, $lessonId, $connection) {
    $stmt = $connection->prepare("
        SELECT file_id, lesson_id, file_type, file_url 
        FROM tbl_lesson_files 
        WHERE file_id = ? AND lesson_id = ? AND file_type = 'Notes'
    ");
    $stmt->bind_param("ii", $fileId, $lessonId);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();
    $stmt->close();
    return $file;
}

// 4. Function to extract text from PDF using the PDF Parser library
function extractPdfTextWithParser($filePath) {
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        if (!empty(trim($text))) {
            return ['success' => true, 'content' => trim($text), 'method' => 'pdfparser'];
        } else {
            return ['success' => false, 'content' => 'PDF_IS_EMPTY_OR_SCANNED', 'method' => 'pdfparser'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'content' => 'PDF_PARSER_ERROR: ' . $e->getMessage(), 'method' => 'pdfparser'];
    }
}

// 5. Function to get file content
function getFileContent($filePath) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'error' => 'File not found', 'path' => $filePath];
    }
    
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    // Handle PDF files - USE THE PDF PARSER!
    if ($extension === 'pdf') {
        return extractPdfTextWithParser($filePath);
    }
    
    // Handle text files
    $textExtensions = ['txt', 'html', 'htm', 'css', 'js', 'php', 'json', 'xml', 'md'];
    if (in_array($extension, $textExtensions)) {
        $content = file_get_contents($filePath);
        return ['success' => true, 'content' => $content !== false ? $content : 'READ_ERROR', 'method' => 'direct'];
    }
    
    return ['success' => true, 'content' => 'UNSUPPORTED_FILE_TYPE', 'method' => 'fallback'];
}

// 6. Main execution
echo "<!DOCTYPE html><html><head><title>File Content Retriever</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { border: 1px solid #ccc; padding: 10px; background: #f9f9f9; white-space: pre-wrap; }
    .file { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; }
    .success { color: #388e3c; background: #e8f5e8; padding: 10px; border-radius: 4px; }
    .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-radius: 4px; }
</style>";
echo "</head><body>";
echo "<h1>File Content Retriever</h1>";

// Check if PDF parser is available
if (class_exists('Smalot\PdfParser\Parser')) {
    echo "<div class='info'>";
    echo "<p>✓ PDF Parser library is available and will be used</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<p>✗ PDF Parser library is NOT available. Run: <code>composer require smalot/pdfparser</code></p>";
    echo "</div>";
}

// Get the specific file where BOTH lesson_id AND file_id match
$file = getFileDetails($file_id, $lesson_id, $connection);

if ($file) {
    // Build the correct file path
    $filePath = __DIR__ . '/' . $file['file_url'];
    $result = getFileContent($filePath);
    
    echo "<div class='file'>";
    echo "<div class='info'>";
    echo "<h2>File Details</h2>";
    echo "<p><strong>File ID:</strong> " . $file['file_id'] . "</p>";
    echo "<p><strong>Lesson ID:</strong> " . $file['lesson_id'] . "</p>";
    echo "<p><strong>File Type:</strong> " . $file['file_type'] . "</p>";
    echo "<p><strong>File Path:</strong> " . $file['file_url'] . "</p>";
    echo "<p><strong>Absolute Path:</strong> " . $filePath . "</p>";
    echo "</div>";
    
    if ($result['success']) {
        echo "<div class='success'>";
        echo "<h3>Content Retrieved Successfully (Method: " . $result['method'] . "):</h3>";
        echo "<pre>" . htmlspecialchars($result['content']) . "</pre>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>Error:</h3>";
        echo "<p>" . $result['content'] . "</p>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h2>No File Found</h2>";
    echo "<p>No file found with File ID: <strong>$file_id</strong> AND Lesson ID: <strong>$lesson_id</strong></p>";
    echo "<p>Please check that both IDs exist in the same database row.</p>";
    echo "</div>";
}

echo "</body></html>";

return isset($result['content']) ? $result['content'] : '';
?>

