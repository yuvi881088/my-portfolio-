<?php
/**
 * Software Automation & Workflow Engine
 * Victorious Innovatech Solutions
 * 
 * Handles automated lead scoring, webhook notifications, and automated log synchronization.
 */

require_once __DIR__ . '/config.php';

/**
 * Automatically process and score an incoming inquiry
 * 
 * @param array $leadData [name, email, subject, message, ip]
 * @return array Automation result with priority, score, and actions taken
 */
function processLeadAutomation(array $leadData) {
    $name    = $leadData['name'] ?? 'Unknown';
    $email   = $leadData['email'] ?? '';
    $subject = $leadData['subject'] ?? '';
    $message = $leadData['message'] ?? '';
    $ip      = $leadData['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    $combinedText = mb_strtolower($subject . ' ' . $message);

    // 1. Automated Lead Scoring Engine
    $score = 50; // Base score
    $priority = 'MEDIUM';
    $tags = [];

    // High intent / Budget keywords
    if (preg_match('/(budget|quote|urgent|asap|hire|contract|timeline|immediate|deal|project|ready)/i', $combinedText)) {
        $score += 30;
        $tags[] = 'high_intent';
    }

    // Technology categorization
    if (preg_match('/(app|mobile|android|flutter|ios|kotlin)/i', $combinedText)) {
        $tags[] = 'mobile_app_inquiry';
        $score += 10;
    }
    if (preg_match('/(automation|automate|bot|script|rpa|sync|api)/i', $combinedText)) {
        $tags[] = 'software_automation_inquiry';
        $score += 15;
    }
    if (preg_match('/(web|website|react|next\.js|ecommerce|store)/i', $combinedText)) {
        $tags[] = 'web_development_inquiry';
        $score += 10;
    }

    if ($score >= 75) {
        $priority = 'HIGH';
    } elseif ($score < 40) {
        $priority = 'LOW';
    }

    // 2. Automated Action Logging to CSV
    $logFile = __DIR__ . '/automation_logs.csv';
    $fileExisted = file_exists($logFile);
    if ($fp = @fopen($logFile, 'a')) {
        if (!$fileExisted) {
            fputcsv($fp, ['Date', 'Name', 'Email', 'Subject', 'Score', 'Priority', 'Tags', 'IP']);
        }
        fputcsv($fp, [
            date('Y-m-d H:i:s'),
            $name,
            $email,
            $subject,
            $score,
            $priority,
            implode('|', $tags),
            $ip
        ]);
        fclose($fp);
    }

    // 3. Optional: Trigger External Webhook (e.g. Slack / Telegram / Zapier)
    $webhookSent = false;
    if (defined('AUTOMATION_WEBHOOK_URL') && AUTOMATION_WEBHOOK_URL) {
        $payload = json_encode([
            'event'     => 'lead.created',
            'timestamp' => date('c'),
            'priority'  => $priority,
            'score'     => $score,
            'lead'      => [
                'name'    => $name,
                'email'   => $email,
                'subject' => $subject,
                'message' => $message
            ],
            'tags'      => $tags
        ]);

        $ch = curl_init(AUTOMATION_WEBHOOK_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        @curl_exec($ch);
        curl_close($ch);
        $webhookSent = true;
    }

    return [
        'success'      => true,
        'score'        => $score,
        'priority'     => $priority,
        'tags'         => $tags,
        'webhook_sent' => $webhookSent,
        'logged'       => true
    ];
}

// Standalone API handler if called directly via HTTP POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['SCRIPT_FILENAME']) === 'automation.php') {
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    $result = processLeadAutomation($data);
    echo json_encode([
        'status'     => 'success',
        'automation' => $result
    ]);
    exit;
}
