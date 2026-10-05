<?php
/**
 * Newsletter Subscription API Endpoint
 * Victorious Innovatech Solutions
 * 
 * Method: POST
 * Accepts: JSON { "email": "user@example.com" } or Form Data (email=user@example.com)
 * Returns: JSON response
 */

// Enable error reporting for development (disable in strict production)
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

// Read input data (handles both JSON and x-www-form-urlencoded/form-data)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$email = null;
if (is_array($data) && isset($data['email'])) {
    $email = trim($data['email']);
} elseif (isset($_POST['email'])) {
    $email = trim($_POST['email']);
}

// Validate input presence
if (empty($email)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email address is required.'
    ]);
    exit;
}

// Sanitize & Validate email format
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

// Optional: Client IP address
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

    // Check if the email is already subscribed
    $checkStmt = $pdo->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = :email LIMIT 1");
    $checkStmt->execute([':email' => $email]);
    $existingSubscriber = $checkStmt->fetch();

    if ($existingSubscriber) {
        if ($existingSubscriber['status'] === 'active') {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'already_subscribed' => true,
                'message' => 'You are already subscribed to our newsletter!'
            ]);
            exit;
        } else {
            // Re-activate if was previously unsubscribed
            $updateStmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'active', ip_address = :ip WHERE id = :id");
            $updateStmt->execute([
                ':ip' => $ipAddress,
                ':id' => $existingSubscriber['id']
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Welcome back! Your newsletter subscription has been reactivated.'
            ]);
            exit;
        }
    }

    // Insert new subscriber
    $insertStmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, ip_address, status) VALUES (:email, :ip, 'active')");
    $insertStmt->execute([
        ':email' => $email,
        ':ip'    => $ipAddress
    ]);

    // Optional: Backup save to CSV file as safety measure so no lead is lost
    $csvFile = __DIR__ . '/subscribers_backup.csv';
    $fileExisted = file_exists($csvFile);
    if ($fp = @fopen($csvFile, 'a')) {
        if (!$fileExisted) {
            fputcsv($fp, ['ID', 'Email', 'IP Address', 'Date']);
        }
        fputcsv($fp, [$pdo->lastInsertId(), $email, $ipAddress, date('Y-m-d H:i:s')]);
        fclose($fp);
    }

    // Send real-time Gmail SMTP notification for new newsletter subscriber
    if (file_exists(__DIR__ . '/mailer.php')) {
        require_once __DIR__ . '/mailer.php';
        if (function_exists('send_newsletter_notification_email')) {
            send_newsletter_notification_email($email, $ipAddress);
        }
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for subscribing to our newsletter!'
    ]);

} catch (PDOException $e) {
    error_log("Database Query Error: " . $e->getMessage());

    // Fallback: Agar table nahi bani ya database error aaye, CSV me save kar lo
    $csvFile = __DIR__ . '/subscribers_backup.csv';
    $fileExisted = file_exists($csvFile);
    if ($fp = @fopen($csvFile, 'a')) {
        if (!$fileExisted) {
            fputcsv($fp, ['ID', 'Email', 'IP Address', 'Date']);
        }
        fputcsv($fp, ['FALLBACK', $email, $ipAddress, date('Y-m-d H:i:s')]);
        fclose($fp);

        // Send real-time Gmail SMTP notification for new newsletter subscriber
        if (file_exists(__DIR__ . '/mailer.php')) {
            require_once __DIR__ . '/mailer.php';
            if (function_exists('send_newsletter_notification_email')) {
                send_newsletter_notification_email($email, $ipAddress);
            }
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for subscribing to our newsletter!'
        ]);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving your subscription. Please try again later.'
    ]);
}
