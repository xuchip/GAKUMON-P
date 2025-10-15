<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_POST['user_id'] ?? '';
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Basic validation
    $errors = [];
    
    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required.";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required.";
    }
    
    // Handle file attachment if exists
    $attachment_info = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        
        // Validate file type
        $allowed_types = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Please upload JPG, PNG, GIF, PDF, DOC, or DOCX files only.";
        }
        
        if ($file['size'] > $max_size) {
            $errors[] = "File size must be less than 5MB.";
        }
        
        if (empty($errors)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/contact_attachments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $attachment_info = [
                    'original_name' => $file['name'],
                    'saved_name' => $filename,
                    'file_path' => $file_path,
                    'file_type' => $file['type'],
                    'file_size' => $file['size']
                ];
            } else {
                $errors[] = "Failed to upload file. Please try again.";
            }
        }
    }
    
    // If no errors, save to database and send email
    if (empty($errors)) {
        try {
            // Save to database
            $stmt = $connection->prepare("
                INSERT INTO tbl_contact_messages 
                (user_id, first_name, last_name, email, subject, message, attachment_name, attachment_path, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $attachment_name = $attachment_info ? $attachment_info['original_name'] : null;
            $attachment_path = $attachment_info ? $attachment_info['saved_name'] : null;
            
            $stmt->bind_param("isssssss", 
                $user_id, 
                $firstName, 
                $lastName, 
                $email, 
                $subject, 
                $message,
                $attachment_name,
                $attachment_path
            );
            
            if ($stmt->execute()) {
                $message_id = $connection->insert_id;
                
                // Send email notification (you'll need to configure your email settings)
                sendContactEmail($firstName, $lastName, $email, $subject, $message, $attachment_info);
                
                // Return success response
                echo json_encode([
                    'success' => true,
                    'message' => 'Your message has been sent successfully! We will get back to you within 24 hours.',
                    'message_id' => $message_id
                ]);
            } else {
                throw new Exception("Failed to save message to database.");
            }
            
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Sorry, there was an error sending your message. Please try again later.'
            ]);
        }
    } else {
        // Return validation errors
        echo json_encode([
            'success' => false,
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        ]);
    }
} else {
    // Invalid request method
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}

// Function to send email notification
function sendContactEmail($firstName, $lastName, $email, $subject, $message, $attachment_info = null) {
    $to = "support@gakumon.com"; // Your support email
    $fullName = $firstName . ' ' . $lastName;
    
    $email_subject = "New Contact Form Submission: " . $subject;
    
    $email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #811212; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .field { margin-bottom: 10px; }
            .label { font-weight: bold; color: #811212; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
            </div>
            <div class='content'>
                <div class='field'><span class='label'>Name:</span> $fullName</div>
                <div class='field'><span class='label'>Email:</span> $email</div>
                <div class='field'><span class='label'>Subject:</span> $subject</div>
                <div class='field'><span class='label'>Message:</span></div>
                <div style='background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #811212;'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                " . ($attachment_info ? "<div class='field'><span class='label'>Attachment:</span> " . $attachment_info['original_name'] . "</div>" : "") . "
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $email,
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // For production, you might want to use PHPMailer or a similar library
    // This is a basic implementation
    @mail($to, $email_subject, $email_body, implode("\r\n", $headers));
}

// Close database connection
if (isset($connection)) {
    $connection->close();
}
?>