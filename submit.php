<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Configuration
    $recipientEmail = 'mtapelikarim7@gmail.com';
    $uploadDir = __DIR__ . '/uploads/';
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Create upload directory if needed
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate inputs
    $errors = [];
    $requiredFields = ['fullName', 'email', 'phone', 'institution', 'courseOfStudy', 'attachmentPeriod', 'reasonForAttachment'];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . " is required";
        }
    }
    
    // Validate email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate phone
    if (!preg_match('/^\+2547[0-9]{8}$/', $_POST['phone'])) {
        $errors[] = "Phone number must be in format +2547XXXXXXXX";
    }
    
    // Validate terms
    if (empty($_POST['termsAccepted'])) {
        $errors[] = "You must accept the terms and conditions";
    }
    
    // Validate CV file
    if (empty($_FILES['cvUpload']['name']) || $_FILES['cvUpload']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "CV upload is required";
    } elseif ($_FILES['cvUpload']['size'] > $maxFileSize) {
        $errors[] = "CV file exceeds 5MB limit";
    } elseif (pathinfo($_FILES['cvUpload']['name'], PATHINFO_EXTENSION) !== 'pdf') {
        $errors[] = "CV must be a PDF file";
    }
    
    // Validate recommendation letter (optional)
    if (!empty($_FILES['recommendationLetter']['name'])) {
        if ($_FILES['recommendationLetter']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading recommendation letter";
        } elseif ($_FILES['recommendationLetter']['size'] > $maxFileSize) {
            $errors[] = "Recommendation letter exceeds 5MB limit";
        } elseif (pathinfo($_FILES['recommendationLetter']['name'], PATHINFO_EXTENSION) !== 'pdf') {
            $errors[] = "Recommendation letter must be a PDF file";
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Save uploaded files
        $cvPath = $uploadDir . sanitizeFilename($_POST['fullName'] . '_CV.pdf');
        move_uploaded_file($_FILES['cvUpload']['tmp_name'], $cvPath);
        
        $recommendationPath = '';
        if (!empty($_FILES['recommendationLetter']['name'])) {
            $recommendationPath = $uploadDir . sanitizeFilename($_POST['fullName'] . '_Recommendation.pdf');
            move_uploaded_file($_FILES['recommendationLetter']['tmp_name'], $recommendationPath);
        }
        
        // Build email message
        $subject = "Attachment Application: " . $_POST['fullName'];
        $message = "NEW ATTACHMENT APPLICATION\n\n";
        $message .= "Full Name: " . sanitizeInput($_POST['fullName']) . "\n";
        $message .= "Email: " . sanitizeInput($_POST['email']) . "\n";
        $message .= "Phone: " . sanitizeInput($_POST['phone']) . "\n";
        $message .= "Institution: " . sanitizeInput($_POST['institution']) . "\n";
        $message .= "Course of Study: " . sanitizeInput($_POST['courseOfStudy']) . "\n";
        $message .= "Attachment Period: " . sanitizeInput($_POST['attachmentPeriod']) . "\n";
        
        if (!empty($_POST['otherPeriod'])) {
            $message .= "Other Period: " . sanitizeInput($_POST['otherPeriod']) . "\n";
        }
        
        $message .= "\nReason for Attachment:\n" . sanitizeInput($_POST['reasonForAttachment']) . "\n";
        
        // Send email with attachments
        $boundary = md5(time());
        $headers = "From: " . $_POST['email'] . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=utf-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message . "\r\n\r\n";
        
        // Attach CV
        $fileContent = file_get_contents($cvPath);
        $base64 = base64_encode($fileContent);
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: application/pdf; name=\"CV.pdf\"\r\n";
        $body .= "Content-Disposition: attachment; filename=\"CV.pdf\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split($base64) . "\r\n";
        
        // Attach recommendation if exists
        if ($recommendationPath && file_exists($recommendationPath)) {
            $fileContent = file_get_contents($recommendationPath);
            $base64 = base64_encode($fileContent);
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: application/pdf; name=\"Recommendation.pdf\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"Recommendation.pdf\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split($base64) . "\r\n";
        }
        
        $body .= "--$boundary--";
        
        // Send email
        if (mail($recipientEmail, $subject, $body, $headers)) {
            // Clean up files
            unlink($cvPath);
            if ($recommendationPath) unlink($recommendationPath);
            
            $response = [
                'success' => true,
                'message' => 'Application submitted successfully!'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to send application. Please try again later.'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Please fix the following errors:',
            'errors' => $errors
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Helper functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
}