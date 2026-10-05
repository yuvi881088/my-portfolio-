<?php
/**
 * Database & API Configuration
 * Victorious Innovatech Solutions
 */

// Database credentials - Apne database ke hisaab se update karein
define('DB_HOST', 'localhost');
define('DB_NAME', 'victorious_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Gmail SMTP Configuration (Real-time Email Delivery)
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'ssl://smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'yuvi881088@gmail.com');
define('SMTP_PASS', 'vfmhwtgbzmdsoljn');
define('ADMIN_EMAIL', 'yuvi881088@gmail.com');
define('SEND_EMAIL_NOTIFICATION', true);

// Gemini AI API Key (agar Gemini cloud AI use karna ho)
// Agar key empty hogi ya invalid hogi, toh built-in smart AI knowledge base automatic chalega!
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');

// Chatbot interactions database me save karne ke liye
define('LOG_CHAT_MESSAGES', true);

// Company Details (Used by AI Bot)
define('COMPANY_NAME', 'Victorious Innovatech Solutions');
define('COMPANY_TAGLINE', 'Engineering Custom Digital Solutions');
define('COMPANY_EMAIL', 'contact@victoriousinnovatechsolutions.com');
define('COMPANY_PHONE', '+91 7970292029');
define('COMPANY_PHONE_FORMATTED', '+91 7970292029');
define('COMPANY_WHATSAPP_URL', 'https://wa.me/917970292029?text=Hello%20Victorious%20Innovatech%20Solutions,%20I%20would%20like%20to%20inquire%20about%20your%20services.');
define('COMPANY_ADDRESS', '102/A S.D. HEIGHTS, Indore, India');

/**
 * Get PDO Database Connection
 * @return PDO|null
 */
function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Warning: " . $e->getMessage());
            return null; // Return null gracefully so APIs with fallback can still work
        }
    }

    return $pdo;
}
