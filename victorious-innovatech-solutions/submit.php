<?php
/**
 * Victorious Innovatech Solutions - Contact Form Submission API
 * File: submit.php
 * 
 * Features:
 * - Accepts JSON and Form-Data POST requests
 * - Full CORS support for cross-origin submissions
 * - Auto-creates and secures 'data/' directory
 * - Stores all submissions in 'data/submissions.json' and individual JSON logs
 * - Sends a responsive HTML email notification to the site administrator
 * - Returns clean JSON response
 */

// 1. CORS & Response Headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed. Only POST requests are accepted.'
    ]);
    exit;
}

// 2. Configuration Settings
$TO_EMAIL = "info@victoriousinnovatechsolutions.com"; // Change to your destination email
$FALLBACK_EMAIL = "victoriousinnovatechsolutions@gmail.com"; // Secondary or backup email
$SITE_NAME = "Victorious Innovatech Solutions";
$DATA_DIR = __DIR__ . '/data';

// 3. Parse Request Data (supports application/json and multipart/form-data)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

// 4. Sanitize and Extract Fields
$name    = isset($data['name']) ? trim(strip_tags($data['name'])) : '';
$email   = isset($data['email']) ? trim(filter_var($data['email'], FILTER_SANITIZE_EMAIL)) : '';
$phone   = isset($data['phone']) ? trim(strip_tags($data['phone'])) : 'Not provided';
$subject = isset($data['subject']) ? trim(strip_tags($data['subject'])) : 'New Website Inquiry';
$message = isset($data['message']) ? trim(strip_tags($data['message'])) : '';

// Honeypot spam check (if 'website' field is present and filled, it's a bot)
if (!empty($data['website'])) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Query received.']);
    exit;
}

// 5. Validation
$errors = [];
if (empty($name)) {
    $errors[] = 'Name is required.';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}
if (empty($message)) {
    $errors[] = 'Message is required.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed.',
        'errors'  => $errors
    ]);
    exit;
}

// 6. Build Record
$timestamp = time();
$dateString = date('Y-m-d H:i:s T');
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
$submissionId = 'query_' . $timestamp . '_' . bin2hex(random_bytes(4));

$record = [
    'id'         => $submissionId,
    'timestamp'  => $timestamp,
    'date'       => $dateString,
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'subject'    => $subject,
    'message'    => $message,
    'ip'         => $ipAddress,
    'user_agent' => $userAgent
];

// 7. Store in data/ folder as JSON
if (!is_dir($DATA_DIR)) {
    mkdir($DATA_DIR, 0755, true);
    // Protect data folder with .htaccess against direct browser access
    file_put_contents($DATA_DIR . '/.htaccess', "Deny from all\n");
    file_put_contents($DATA_DIR . '/index.php', "<?php http_response_code(403); exit('Forbidden');");
}

// Save individual record
$individualFile = $DATA_DIR . '/' . $submissionId . '.json';
file_put_contents($individualFile, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Append to master submissions.json file
$masterFile = $DATA_DIR . '/submissions.json';
$allSubmissions = [];
if (file_exists($masterFile)) {
    $existing = json_decode(file_get_contents($masterFile), true);
    if (is_array($existing)) {
        $allSubmissions = $existing;
    }
}
$allSubmissions[] = $record;
file_put_contents($masterFile, json_encode($allSubmissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// 8. Prepare and Send HTML Email
$emailSubject = "[$SITE_NAME Inquiry] " . $subject . " from " . $name;

$htmlBody = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; }
  .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
  .header { background: linear-gradient(135deg, #EA580C 0%, #FB923C 100%); padding: 28px 24px; text-align: center; color: #ffffff; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
  .header p { margin: 6px 0 0 0; font-size: 13px; opacity: 0.9; }
  .content { padding: 30px 24px; color: #1e293b; }
  .field { margin-bottom: 20px; }
  .label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 4px; }
  .value { font-size: 15px; color: #0f172a; font-weight: 500; }
  .message-box { background: #f8fafc; border-left: 4px solid #EA580C; padding: 16px; border-radius: 6px; font-size: 14px; line-height: 1.6; color: #334155; margin-top: 8px; white-space: pre-wrap; }
  .footer { background: #0f1115; padding: 18px 24px; text-align: center; font-size: 12px; color: #94a3b8; }
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>New Contact Query Received</h1>
    <p>' . htmlspecialchars($SITE_NAME) . ' Website Contact Form</p>
  </div>
  <div class="content">
    <div class="field">
      <div class="label">Full Name</div>
      <div class="value">' . htmlspecialchars($name) . '</div>
    </div>
    <div class="field">
      <div class="label">Email Address</div>
      <div class="value"><a href="mailto:' . htmlspecialchars($email) . '" style="color:#EA580C;text-decoration:none;font-weight:600;">' . htmlspecialchars($email) . '</a></div>
    </div>
    <div class="field">
      <div class="label">Phone Number</div>
      <div class="value">' . htmlspecialchars($phone) . '</div>
    </div>
    <div class="field">
      <div class="label">Subject / Service Interest</div>
      <div class="value">' . htmlspecialchars($subject) . '</div>
    </div>
    <div class="field">
      <div class="label">Project Details / Message</div>
      <div class="message-box">' . nl2br(htmlspecialchars($message)) . '</div>
    </div>
    <div style="border-top: 1px solid #e2e8f0; margin-top: 24px; padding-top: 16px; font-size: 11px; color: #94a3b8;">
      <strong>Query ID:</strong> ' . $submissionId . '<br>
      <strong>Received:</strong> ' . $dateString . '<br>
      <strong>IP Address:</strong> ' . $ipAddress . '
    </div>
  </div>
  <div class="footer">
    &copy; ' . date('Y') . ' ' . htmlspecialchars($SITE_NAME) . '. All rights reserved.
  </div>
</div>
</body>
</html>
';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: " . $SITE_NAME . " <noreply@" . ($_SERVER['SERVER_NAME'] ?? 'victoriousinnovatechsolutions.com') . ">\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send Mail (with fallback)
$mailSent = @mail($TO_EMAIL, $emailSubject, $htmlBody, $headers);
if (!$mailSent && !empty($FALLBACK_EMAIL) && $FALLBACK_EMAIL !== $TO_EMAIL) {
    $mailSent = @mail($FALLBACK_EMAIL, $emailSubject, $htmlBody, $headers);
}

// 9. Response
http_response_code(200);
echo json_encode([
    'success'   => true,
    'message'   => 'Thank you! Your query has been submitted successfully.',
    'id'        => $submissionId,
    'saved'     => true,
    'mail_sent' => $mailSent
]);
