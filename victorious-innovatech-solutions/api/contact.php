<?php
/**
 * Contact Form API Endpoint
 * Victorious Innovatech Solutions
 * 
 * Method: POST
 * Accepts: JSON { "name": "...", "email": "...", "subject": "...", "message": "..." } 
 *          or Form Data
 * Returns: JSON response
 */

// Error handling settings
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORS Headers - Allow cross-origin requests from frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed. Only POST requests are supported.'
    ]);
    exit;
}

// Read input data (handles both JSON and form data)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

// Extract fields
$name    = isset($data['name']) ? trim($data['name']) : '';
$email   = isset($data['email']) ? trim($data['email']) : '';
$subject = isset($data['subject']) ? trim($data['subject']) : '';
$message = isset($data['message']) ? trim($data['message']) : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required.';
} elseif (mb_strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters long.';
}

if (empty($email)) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($subject)) {
    $subject = 'General Website Inquiry';
}

if (empty($message)) {
    $errors[] = 'Message is required.';
} elseif (mb_strlen($message) < 2) {
    $errors[] = 'Message must be at least 2 characters long.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors),
        'errors'  => $errors
    ]);
    exit;
}

// Sanitize inputs
$name    = htmlspecialchars(strip_tags($name), ENT_QUOTES, 'UTF-8');
$email   = filter_var($email, FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars(strip_tags($subject), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(strip_tags($message), ENT_QUOTES, 'UTF-8');

// Client IP Address
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ipAddress = trim($ipList[0]);
}

// Include database configuration
require_once __DIR__ . '/config.php';

try {
    $pdo = getDbConnection();
    if (!$pdo) {
        throw new PDOException("Database connection is not available");
    }

    // Insert inquiry into database
    $stmt = $pdo->prepare("INSERT INTO contact_inquiries (name, email, subject, message, ip_address, status) VALUES (:name, :email, :subject, :message, :ip, 'new')");
    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip'      => $ipAddress
    ]);
    $inquiryId = $pdo->lastInsertId();

    // Backup to CSV file so no inquiry is ever lost
    $csvFile = __DIR__ . '/inquiries_backup.csv';
    $fileExisted = file_exists($csvFile);
    if ($fp = @fopen($csvFile, 'a')) {
        if (!$fileExisted) {
            fputcsv($fp, ['ID', 'Name', 'Email', 'Subject', 'Message', 'IP Address', 'Date']);
        }
        fputcsv($fp, [$inquiryId, $name, $email, $subject, $message, $ipAddress, date('Y-m-d H:i:s')]);
        fclose($fp);
    }

    // Trigger Software Automation Engine
    if (file_exists(__DIR__ . '/automation.php')) {
        require_once __DIR__ . '/automation.php';
        processLeadAutomation([
            'name'    => $name,
            'email'   => $email,
            'subject' => $subject,
            'message' => $message,
            'ip'      => $ipAddress
        ]);
    }

    // Send real-time email notification (via Gmail SMTP or server mail)
    if (file_exists(__DIR__ . '/mailer.php')) {
        require_once __DIR__ . '/mailer.php';
        send_lead_notification_email($name, $email, $subject, $message, $ipAddress);
    } elseif (defined('SEND_EMAIL_NOTIFICATION') && SEND_EMAIL_NOTIFICATION && defined('ADMIN_EMAIL')) {
        $to = ADMIN_EMAIL;
        $mailSubject = "New Contact Inquiry: " . $subject;
        $mailBody = "You received a new message from your website contact form:\n\n"
                  . "Name: $name\n"
                  . "Email: $email\n"
                  . "Subject: $subject\n"
                  . "IP: $ipAddress\n\n"
                  . "Message:\n$message\n";
        $headers = "From: no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'victoriousinnovatech.com') . "\r\n"
                 . "Reply-To: $email\r\n"
                 . "X-Mailer: PHP/" . phpversion();
        @mail($to, $mailSubject, $mailBody, $headers);
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for reaching out! Our team will get back to you within 24 business hours.'
    ]);

} catch (PDOException $e) {
    error_log("Database Insert Error: " . $e->getMessage());

    // Fallback: Agar database down ho, tab bhi inquiry CSV me save hogi
    $csvFile = __DIR__ . '/inquiries_backup.csv';
    $fileExisted = file_exists($csvFile);
    if ($fp = @fopen($csvFile, 'a')) {
        if (!$fileExisted) {
            fputcsv($fp, ['ID', 'Name', 'Email', 'Subject', 'Message', 'IP Address', 'Date']);
        }
        fputcsv($fp, ['FALLBACK', $name, $email, $subject, $message, $ipAddress, date('Y-m-d H:i:s')]);
        fclose($fp);

        // Trigger Software Automation Engine
        if (file_exists(__DIR__ . '/automation.php')) {
            require_once __DIR__ . '/automation.php';
            processLeadAutomation([
                'name'    => $name,
                'email'   => $email,
                'subject' => $subject,
                'message' => $message,
                'ip'      => $ipAddress
            ]);
        }

        // Send real-time email notification (via Gmail SMTP or server mail)
        if (file_exists(__DIR__ . '/mailer.php')) {
            require_once __DIR__ . '/mailer.php';
            send_lead_notification_email($name, $email, $subject, $message, $ipAddress);
        } elseif (defined('SEND_EMAIL_NOTIFICATION') && SEND_EMAIL_NOTIFICATION && defined('ADMIN_EMAIL')) {
            $to = ADMIN_EMAIL;
            $mailSubject = "New Website Lead: " . $subject;
            $mailBody = "You received a new inquiry from your website contact form:\n\n"
                      . "Name: $name\n"
                      . "Email: $email\n"
                      . "Subject: $subject\n"
                      . "IP: $ipAddress\n"
                      . "Time: " . date('Y-m-d H:i:s') . "\n\n"
                      . "Message:\n$message\n";
            $headers = "From: Victorious Leads <no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'victoriousinnovatech.com') . ">\r\n"
                     . "Reply-To: $email\r\n"
                     . "X-Mailer: PHP/" . phpversion();
            @mail($to, $mailSubject, $mailBody, $headers);
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for reaching out! Our team will get back to you within 24 business hours.'
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while submitting your message. Please try again later.'
    ]);
}
