<?php

// Include security utilities
require_once __DIR__ . '/../security/security.php';
setSecurityHeaders();
setSecureCORS();

header('Content-Type: application/json; charset=utf-8');

/*composer needs to be installed in order to enable mailing services
Get composer from getcomposer.org
Run in cmd at dorm-mart
composer require phpmailer/phpmailer

If composer cannot be installed or is giving errors then follow the following steps:
1. Download PHPMailer ZIP: https://github.com/PHPMailer/PHPMailer/releases
2. Extract src/ into dorm-mart/vendor/PHPMailer/src
*/


// Include shared email utility (uses load_env.php, optimized SMTP settings)
require_once __DIR__ . '/../utility/email_sender.php';




function generatePassword(int $length = 12): string
{
    if ($length < 8) $length = 8;

    $uppers = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowers = 'abcdefghijklmnopqrstuvwxyz';
    $digits = '0123456789';
    $special = '!@#$%^&*()-_=+[]{};:,.?/';

    // ensure each required class is present
    $password = [
        $uppers[random_int(0, strlen($uppers) - 1)],
        $lowers[random_int(0, strlen($lowers) - 1)],
        $digits[random_int(0, strlen($digits) - 1)],
        $special[random_int(0, strlen($special) - 1)],
    ];

    // fill the rest
    $all = $uppers . $lowers . $digits . $special;
    for ($i = count($password); $i < $length; $i++) {
        $password[] = $all[random_int(0, strlen($all) - 1)];
    }

    // secure shuffle (Fisher–Yates)
    for ($i = count($password) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
    }

    return implode('', $password);
}

// Example:
// echo generatePassword(12);

function sendWelcomeGmail(array $user, string $tempPassword): array
{
    $first = $user['firstName'] ?: 'Student';
    $subject = 'Welcome to Dorm Mart';
    $toName = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));

    // Exact same email template as before (preserved for compatibility)
    $html = <<<HTML
<!doctype html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{$subject}</title>
  </head>
  <body style="font-family:Arial,Helvetica,sans-serif;line-height:1.5;color:#111;margin:0;padding:16px;background:#111;">
    <div style="max-width:640px;margin:0 auto;background:#1e1e1e;border-radius:8px;padding:20px;">
      <p style="color:#eee;">Dear {$first},</p>
      <p style="color:#eee;">Welcome to <strong>Dorm Mart</strong> &mdash; the student marketplace for UB.</p>
      <p style="color:#eee;">Here is your temporary (current) password. <strong>DO NOT</strong> share this with anyone.</p>
      <p style="font-size:20px;color:#fff;"><strong>{$tempPassword}</strong></p>
      <p style="color:#eee;">If you want to change this password, go to <em>Settings &rarr; Change Password</em>.</p>
      <p style="color:#eee;">Happy trading,<br/>The Dorm Mart Team</p>
      <hr style="border:none;border-top:1px solid #333;margin:16px 0;">
      <p style="font-size:12px;color:#aaa;">This is an automated message; do not reply. For support:
      <a href="mailto:dormmartsupport@gmail.com" style="color:#9db7ff;">dormmartsupport@gmail.com</a></p>
    </div>
  </body>
</html>
HTML;

    // Plain-text part (exact same as before)
    $text = <<<TEXT
Dear {$first},

Welcome to Dorm Mart - the student marketplace for UB.

Here is your temporary (current) password. DO NOT share this with anyone.

{$tempPassword}

If you want to change this password, go to Settings -> Change Password.

Happy trading,
The Dorm Mart Team

(This is an automated message; do not reply. Support: dormmartsupport@gmail.com)
TEXT;

    // Use shared email utility (optimized SMTP settings, connection reuse)
    $result = sendEmail($user['email'], $toName, $subject, $html, $text);
    
    // Preserve original return format ['ok' => bool, 'error' => string|null]
    if ($result['success']) {
        return ['ok' => true, 'error' => null];
    } else {
        return ['ok' => false, 'error' => $result['error'] ?? 'Failed to send email'];
    }
}

function sendPromoWelcomeEmail(array $user): array
{
    $first = $user['firstName'] ?: 'Student';
    $subject = 'Welcome to Dorm Mart Promotional Updates';
    $toName = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));

    // Exact same email template as before (preserved for compatibility)
    $html = <<<HTML
<!doctype html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{$subject}</title>
  </head>
  <body style="font-family:Arial,Helvetica,sans-serif;line-height:1.5;color:#111;margin:0;padding:16px;background:#111;">
    <div style="max-width:640px;margin:0 auto;background:#1e1e1e;border-radius:12px;padding:24px;border:1px solid #333;">
      <div style="text-align:center;margin-bottom:24px;">
        <h1 style="color:#2563EB;margin:0;font-size:24px;font-weight:bold;">📧 Promotional Updates</h1>
        <div style="width:60px;height:3px;background:linear-gradient(90deg, #2563EB, #1d4ed8);margin:8px auto;border-radius:2px;"></div>
      </div>
      
      <p style="color:#eee;font-size:16px;margin:0 0 16px 0;">Dear {$first},</p>
      
      <p style="color:#eee;margin:0 0 20px 0;">Thank you for opting into promotional updates from <strong style="color:#2563EB;">Dorm Mart</strong>!</p>
      
      <div style="background:#2a2a2a;border-radius:8px;padding:20px;margin:20px 0;border-left:4px solid #2563EB;">
        <p style="color:#eee;margin:0 0 12px 0;font-weight:bold;">You'll now receive updates about:</p>
        <ul style="color:#ddd;margin:0;padding-left:20px;">
          <li style="margin:6px 0;">Emails about your notifcations tab</li>
          <li style="margin:6px 0;">New website news and updates</li>
        </ul>
      </div>
      
      <p style="color:#eee;margin:20px 0;">This is a one-time email for the first time you ever sign up for promotional updates with an account. We promise to keep our emails relevant and not overwhelm your inbox. You can always update your preferences in your account settings.</p>
      
      <div style="text-align:center;margin:24px 0;">
        <div style="display:inline-block;background:#333;padding:12px 24px;border-radius:6px;border:1px solid #2563EB;">
          <span style="color:#2563EB;font-weight:bold;">✓ Successfully Subscribed</span>
        </div>
      </div>
      
      <p style="color:#eee;margin:20px 0 0 0;">
        Happy trading,<br/>
        <strong style="color:#2563EB;">The Dorm Mart Team</strong>
      </p>
      
      <hr style="border:none;border-top:1px solid #333;margin:20px 0;">
      <p style="font-size:12px;color:#aaa;margin:0;">This is an automated message; do not reply. For support:
      <a href="mailto:dormmartsupport@gmail.com" style="color:#2563EB;">dormmartsupport@gmail.com</a></p>
    </div>
  </body>
</html>
HTML;

    // Plain-text version (exact same as before)
    $text = <<<TEXT
Promotional Updates - Dorm Mart

Dear {$first},

Thank you for opting into promotional updates from Dorm Mart!

You'll now receive updates about:
- Important updates and announcements
- New features and improvements  
- Campus marketplace tips

We promise to keep our emails relevant and not overwhelm your inbox. You can always update your preferences in your account settings.

✓ Successfully Subscribed

Happy trading,
The Dorm Mart Team

(This is an automated message; do not reply. Support: dormmartsupport@gmail.com)
TEXT;

    // Use shared email utility (optimized SMTP settings, connection reuse)
    $result = sendEmail($user['email'], $toName, $subject, $html, $text);
    
    // Preserve original return format ['ok' => bool, 'error' => string|null]
    if ($result['success']) {
        return ['ok' => true, 'error' => null];
    } else {
        return ['ok' => false, 'error' => $result['error'] ?? 'Failed to send email'];
    }
}



// Include security headers for XSS protection
require_once __DIR__ . '/../security/security.php';
setSecurityHeaders();
setSecureCORS();

header('Content-Type: application/json; charset=utf-8');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Enforce POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Read the JSON body from React's fetch()
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Handle bad JSON
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
    exit;
}

// Extract the values (before validation)
$firstNameRaw = trim($data['firstName'] ?? '');
$lastNameRaw = trim($data['lastName'] ?? '');
$emailRaw = strtolower(trim($data['email'] ?? ''));

// XSS PROTECTION: Check for XSS patterns in firstName and lastName fields
// Note: SQL injection is already prevented by prepared statements and regex validation
if (containsXSSPattern($firstNameRaw) || containsXSSPattern($lastNameRaw)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input format']);
    exit;
}

// XSS PROTECTION: Input validation with regex patterns to prevent XSS attacks
$firstName = validateInput($firstNameRaw, 100, '/^[a-zA-Z\s\-\']+$/');
$lastName = validateInput($lastNameRaw, 100, '/^[a-zA-Z\s\-\']+$/');
$gradMonth = sanitize_number($data['gradMonth'] ?? 0, 1, 12);
$gradYear  = sanitize_number($data['gradYear'] ?? 0, 1900, 2030);
$email = validateInput($emailRaw, 255, '/^[^@\s]+@buffalo\.edu$/');
$promos    = !empty($data['promos']);

if ($firstName === false || $lastName === false || $email === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input format']);
    exit;
}

// Validate
if ($firstName === '' || $lastName === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

if (!preg_match('/^[^@\s]+@buffalo\.edu$/', $email)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email must be @buffalo.edu']);
    exit;
}
// --- Validate graduation date format ---
if ($gradMonth < 1 || $gradMonth > 12 || $gradYear < 1900) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid graduation date']);
    exit;
}

// --- Current and limit dates ---
$currentYear  = (int)date('Y');
$currentMonth = (int)date('n');
$maxFutureYear = $currentYear + 8;

// --- Check for past date ---
if ($gradYear < $currentYear || ($gradYear === $currentYear && $gradMonth < $currentMonth)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Graduation date cannot be in the past']);
    exit;
}

// --- Check for excessive future date ---
if ($gradYear > $maxFutureYear || ($gradYear === $maxFutureYear && $gradMonth > $currentMonth)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Graduation date cannot be more than 8 years in the future']);
    exit;
}

require "../database/db_connect.php";
$conn = db();
try {
    // ============================================================================
    // SQL INJECTION PROTECTION: Prepared Statement with Parameter Binding
    // ============================================================================
    // Check if email already exists using prepared statement.
    // The '?' placeholder and bind_param() ensure $email is treated as data, not SQL.
    // This prevents SQL injection even if malicious SQL code is in the email field.
    // ============================================================================
    $chk = $conn->prepare('SELECT user_id FROM user_accounts WHERE email = ? LIMIT 1');
    $chk->bind_param('s', $email);  // 's' = string type, safely bound as parameter
    $chk->execute();
    $chk->store_result();                   // needed to use num_rows without fetching
    if ($chk->num_rows > 0) {
        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }
    $chk->close();

    // 2) Generate & hash password
    // SECURITY NOTE:
    // We NEVER store plaintext passwords. We generate a temporary password for the
    // new user and immediately hash it with password_hash(), which automatically
    // generates a unique SALT and embeds it into the returned hash (bcrypt here).
    // The database only stores this salted, one-way hash (column: hash_pass).
    $tempPassword = generatePassword(12);
    $hashPass     = password_hash($tempPassword, PASSWORD_BCRYPT);

    // 3) Insert user
    // ============================================================================
    // SQL INJECTION PROTECTION: Prepared Statement with Parameter Binding
    // ============================================================================
    // All user input (firstName, lastName, email, etc.) is inserted using prepared statement
    // with parameter binding. The '?' placeholders ensure user input is treated as data,
    // not executable SQL. This prevents SQL injection attacks even if malicious SQL code
    // is present in any of the input fields.
    // ============================================================================
    $sql = 'INSERT INTO user_accounts
          (first_name, last_name, grad_month, grad_year, email, promotional, hash_pass, hash_auth, join_date, seller, theme, received_intro_promo_email)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, NULL, CURRENT_DATE, 0, 0, ?)';

    $ins = $conn->prepare($sql);
    /*
    types: s=string, i=int
    first_name(s), last_name(s), grad_month(i), grad_year(i),
    email(s), promotional(i), hash_pass(s), hash_auth(s), received_intro_promo_email(i)
*/
    $promotional = $promos ? 1 : 0;
    $receivedIntroPromoEmail = $promos ? 1 : 0; // Set to TRUE if promotional emails are enabled
    $ins->bind_param(
        'ssiisisi',
        $firstName,
        $lastName,
        $gradMonth,
        $gradYear,
        $email,
        $promotional,
        $hashPass,
        $receivedIntroPromoEmail,
    );

    $ok = $ins->execute();
    $ins->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Insert failed']);
        exit;
    }

    // Send welcome email (ignore result here)
    sendWelcomeGmail(["firstName" => $firstName, "lastName" => $lastName, "email" => $email], $tempPassword);

    // Send promo welcome email if user opted into promotional emails
    if ($promos) {
        $promoEmailResult = sendPromoWelcomeEmail(["firstName" => $firstName, "lastName" => $lastName, "email" => $email]);
        if (!$promoEmailResult['ok']) {
            error_log("Failed to send promo welcome email during account creation: " . $promoEmailResult['error']);
        }
    }

    // Success
    echo json_encode([
        'ok' => true
    ]);
} catch (Throwable $e) {
    // Log $e->getMessage() server-side
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => "There was an error"]);
}
