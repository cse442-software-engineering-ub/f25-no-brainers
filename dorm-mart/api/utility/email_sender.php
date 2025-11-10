<?php
/**
 * Shared Email Sending Utility
 * 
 * Centralized email sending function with optimized SMTP settings.
 * Uses load_env.php for environment variables to avoid redundant loading.
 * 
 * Performance optimizations:
 * - SMTPKeepAlive enabled for connection reuse
 * - Optimized SSL/TLS settings
 * - Reduced timeout for faster failure detection
 */

require_once __DIR__ . '/load_env.php';

// Load environment variables once (using centralized utility)
load_env();

// Ensure PHPMailer is available
$PROJECT_ROOT = dirname(__DIR__, 2);
if (file_exists($PROJECT_ROOT . '/vendor/autoload.php')) {
    require_once $PROJECT_ROOT . '/vendor/autoload.php';
} else {
    require_once $PROJECT_ROOT . '/vendor/PHPMailer/src/PHPMailer.php';
    require_once $PROJECT_ROOT . '/vendor/PHPMailer/src/SMTP.php';
    require_once $PROJECT_ROOT . '/vendor/PHPMailer/src/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using optimized SMTP settings
 * 
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $htmlBody HTML email body
 * @param string $textBody Plain text email body
 * @return array ['success' => bool, 'message' => string, 'error' => string|null]
 */
function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): array
{
    // Ensure PHP is using UTF-8 internally
    if (function_exists('mb_internal_encoding')) {
        @mb_internal_encoding('UTF-8');
    }

    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration with performance optimizations
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('GMAIL_USERNAME');
        $mail->Password   = getenv('GMAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Performance optimizations
        $mail->Timeout = 15; // Reduced timeout for faster failure detection
        $mail->SMTPKeepAlive = true; // Enable connection reuse within same request
        $mail->SMTPAutoTLS = true; // Automatic TLS negotiation
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Tell PHPMailer we are sending UTF-8 and how to encode it
        $mail->CharSet   = 'UTF-8';
        $mail->Encoding  = 'base64';

        // From/To addresses
        $mail->setFrom(getenv('GMAIL_USERNAME'), 'Dorm Mart');
        $mail->addReplyTo(getenv('GMAIL_USERNAME'), 'Dorm Mart Support');
        $mail->addAddress($toEmail, $toName);
        
        // Set email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;

        // Send email with timing
        $sendStartTime = microtime(true);
        $mail->send();
        $sendEndTime = microtime(true);
        $sendDuration = round(($sendEndTime - $sendStartTime) * 1000, 2);
        
        // Log performance
        error_log("PHPMailer send() duration: {$sendDuration}ms");
        
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Failed to send email: ' . $e->getMessage()];
    }
}

