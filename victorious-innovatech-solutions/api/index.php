<?php
/**
 * Victorious Innovatech Solutions - PHP API Gateway
 */
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

echo json_encode([
    'status' => 'online',
    'service' => 'Victorious Innovatech Solutions API',
    'version' => '1.0.0',
    'endpoints' => [
        'POST /api/contact.php' => 'Contact Form Inquiry Endpoint',
        'POST /api/newsletter.php' => 'Newsletter Subscription Endpoint',
        'POST /api/chat.php' => 'AI Chatbot Assistant Endpoint',
        'GET  /api/config.php' => 'Configuration (Restricted)'
    ],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
