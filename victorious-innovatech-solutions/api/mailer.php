<?php
/**
 * SMTP Mailer for Victorious Innovatech Solutions
 * Sends real-time lead notification emails directly via Gmail SMTP.
 */

function send_lead_notification_email($name, $clientEmail, $subject, $message, $ipAddress) {
    if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
        return false;
    }

    $smtpUser = defined('SMTP_USER') ? SMTP_USER : 'yuvi881088@gmail.com';
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : 'vfmhwtgbzmdsoljn';
    $host = defined('SMTP_HOST') ? SMTP_HOST : 'ssl://smtp.gmail.com';
    $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
    $timeout = 15;

    // Recipients list
    $recipients = [];
    if (defined('ADMIN_EMAIL') && !empty(ADMIN_EMAIL)) {
        $recipients[] = ADMIN_EMAIL;
    }

    if (empty($recipients)) {
        $recipients[] = $smtpUser;
    }

    $mailSubject = "🚀 New Lead: " . $subject . " - " . $name;

    $currentTime = date('d-M-Y h:i:s A');
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; }
    .card { max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
    .header { background: linear-gradient(135deg, #2563eb, #7c3aed); padding: 24px; text-align: center; }
    .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
    .header p { margin: 6px 0 0 0; color: #e2e8f0; font-size: 14px; }
    .content { padding: 28px; }
    .field { margin-bottom: 18px; }
    .label { font-size: 12px; text-transform: uppercase; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; }
    .value { font-size: 15px; color: #f1f5f9; margin-top: 4px; font-weight: 500; background: #0f172a; padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; }
    .message-box { font-size: 15px; color: #f8fafc; margin-top: 4px; line-height: 1.6; background: #0f172a; padding: 14px; border-radius: 8px; border-left: 4px solid #3b82f6; white-space: pre-wrap; }
    .footer { background: #0f172a; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #334155; }
</style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Victorious Innovatech Solutions</h1>
            <p>New Contact Inquiry Received</p>
        </div>
        <div class="content">
            <div class="field">
                <div class="label">Client Name</div>
                <div class="value">{$name}</div>
            </div>
            <div class="field">
                <div class="label">Email Address</div>
                <div class="value"><a href="mailto:{$clientEmail}" style="color: #60a5fa; text-decoration: none;">{$clientEmail}</a></div>
            </div>
            <div class="field">
                <div class="label">Subject</div>
                <div class="value">{$subject}</div>
            </div>
            <div class="field">
                <div class="label">Inquiry Message</div>
                <div class="message-box">{$message}</div>
            </div>
            <div class="field" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <div class="label">Time</div>
                    <div class="value" style="font-size: 13px;">{$currentTime}</div>
                </div>
                <div style="flex: 1;">
                    <div class="label">IP Address</div>
                    <div class="value" style="font-size: 13px;">{$ipAddress}</div>
                </div>
            </div>
        </div>
        <div class="footer">
            Automated Lead Notification &bull; Victorious Innovatech Solutions
        </div>
    </div>
</body>
</html>
HTML;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client("$host:$port", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        error_log("SMTP Connect Failed: $errstr ($errno)");
        return false;
    }

    $read = function() use ($socket) {
        $res = "";
        while ($line = fgets($socket, 512)) {
            $res .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        return $res;
    };

    $write = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $read();
    $write("EHLO localhost");
    $read();
    $write("AUTH LOGIN");
    $read();
    $write(base64_encode($smtpUser));
    $read();
    $write(base64_encode($smtpPass));
    $authRes = $read();

    if (strpos($authRes, '235') === false) {
        error_log("SMTP Auth Failed: $authRes");
        fclose($socket);
        return false;
    }

    $write("MAIL FROM: <$smtpUser>");
    $read();

    foreach ($recipients as $to) {
        $write("RCPT TO: <$to>");
        $read();
    }

    $write("DATA");
    $read();

    $toHeader = implode(', ', array_map(function($r) { return "<$r>"; }, $recipients));

    $headers = "From: Victorious Leads <$smtpUser>\r\n" .
               "To: $toHeader\r\n" .
               "Reply-To: $clientEmail\r\n" .
               "Subject: $mailSubject\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n";

    $content = $headers . "\r\n" . $htmlBody . "\r\n.\r\n";
    fputs($socket, $content);
    $sendRes = $read();

    $write("QUIT");
    $read();
    fclose($socket);

    return (strpos($sendRes, '250') !== false);
}

/**
 * Send real-time Gmail SMTP notification when a new user subscribes to the newsletter
 */
function send_newsletter_notification_email($subscriberEmail, $ipAddress = '') {
    if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
        return false;
    }

    $smtpUser = defined('SMTP_USER') ? SMTP_USER : 'yuvi881088@gmail.com';
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : 'vfmhwtgbzmdsoljn';
    $host = defined('SMTP_HOST') ? SMTP_HOST : 'ssl://smtp.gmail.com';
    $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
    $timeout = 15;

    $recipients = [];
    if (defined('ADMIN_EMAIL') && !empty(ADMIN_EMAIL)) {
        $recipients[] = ADMIN_EMAIL;
    }
    if (empty($recipients)) {
        $recipients[] = $smtpUser;
    }

    $mailSubject = "📬 New Newsletter Subscriber: " . $subscriberEmail;
    $currentTime = date('d-M-Y h:i:s A');

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #f8fafc; padding: 20px; }
    .card { max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
    .header { background: linear-gradient(135deg, #ea580c, #f97316); padding: 24px; text-align: center; }
    .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
    .header p { margin: 6px 0 0 0; color: #ffedd5; font-size: 14px; }
    .content { padding: 28px; }
    .field { margin-bottom: 18px; }
    .label { font-size: 12px; text-transform: uppercase; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; }
    .value { font-size: 16px; color: #f1f5f9; margin-top: 4px; font-weight: 600; background: #0f172a; padding: 12px 16px; border-radius: 8px; border: 1px solid #334155; }
    .footer { background: #0f172a; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #334155; }
</style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Victorious Innovatech Solutions</h1>
            <p>New Newsletter Subscription Received</p>
        </div>
        <div class="content">
            <div class="field">
                <div class="label">Subscriber Email</div>
                <div class="value"><a href="mailto:{$subscriberEmail}" style="color: #fb923c; text-decoration: none;">{$subscriberEmail}</a></div>
            </div>
            <div class="field" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <div class="label">Subscribed At</div>
                    <div class="value" style="font-size: 13px;">{$currentTime}</div>
                </div>
                <div style="flex: 1;">
                    <div class="label">IP Address</div>
                    <div class="value" style="font-size: 13px;">{$ipAddress}</div>
                </div>
            </div>
        </div>
        <div class="footer">
            Newsletter Notification &bull; Victorious Innovatech Solutions
        </div>
    </div>
</body>
</html>
HTML;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client("$host:$port", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        error_log("SMTP Connect Failed: $errstr ($errno)");
        return false;
    }

    $read = function() use ($socket) {
        $res = "";
        while ($line = fgets($socket, 512)) {
            $res .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
        return $res;
    };

    $write = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $read();
    $write("EHLO localhost");
    $read();
    $write("AUTH LOGIN");
    $read();
    $write(base64_encode($smtpUser));
    $read();
    $write(base64_encode($smtpPass));
    $authRes = $read();

    if (strpos($authRes, '235') === false) {
        error_log("SMTP Auth Failed: $authRes");
        fclose($socket);
        return false;
    }

    $write("MAIL FROM: <$smtpUser>");
    $read();

    foreach ($recipients as $to) {
        $write("RCPT TO: <$to>");
        $read();
    }

    $write("DATA");
    $read();

    $toHeader = implode(', ', array_map(function($r) { return "<$r>"; }, $recipients));

    $headers = "From: Victorious Newsletter <$smtpUser>\r\n" .
               "To: $toHeader\r\n" .
               "Reply-To: $subscriberEmail\r\n" .
               "Subject: $mailSubject\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n";

    $content = $headers . "\r\n" . $htmlBody . "\r\n.\r\n";
    fputs($socket, $content);
    $sendRes = $read();

    $write("QUIT");
    $read();
    fclose($socket);

    return (strpos($sendRes, '250') !== false);
}

