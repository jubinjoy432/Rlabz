<?php
/**
 * Cron Script for SSL Certificate Expiry Reminders
 * 
 * Instructions:
 * 1. Set this script to run daily via cron job (e.g. `0 8 * * * php /path/to/cron_ssl_reminder.php`)
 * 2. Ensure PHPMailer is installed via Composer: `composer require phpmailer/phpmailer`
 * 3. Update the SMTP settings below with your email credentials.
 */

// Only allow execution from CLI or a specific secure token in URL
if (php_sapi_name() !== 'cli' && !isset($_GET['token'])) {
    die("Access denied.");
}

require_once __DIR__ . '/db.php';

// If you have composer installed, uncomment the next line:
// require __DIR__ . '/../../vendor/autoload.php';

// OR if you downloaded PHPMailer manually, include it here:
/*
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
*/

echo "Starting SSL Expiry Check...\n";

try {
    // Check for certificates expiring in exactly 30, 7, and 1 days.
    // DATEDIFF(expiry_date, CURDATE()) gives the difference in days.
    $stmt = $pdo->query("SELECT p.title, s.domain_url, s.expiry_date, s.provider, DATEDIFF(s.expiry_date, CURDATE()) as days_left 
                         FROM project_ssl_certs s 
                         JOIN projects p ON s.project_id = p.id 
                         WHERE s.expiry_date IS NOT NULL 
                         AND DATEDIFF(s.expiry_date, CURDATE()) IN (30, 7, 1)");
    
    $expiringCerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($expiringCerts)) {
        echo "No certificates expiring in 30, 7, or 1 days.\n";
        exit;
    }

    echo "Found " . count($expiringCerts) . " certificate(s) requiring a reminder.\n";

    // Prepare email body
    $emailBody = "<h2>SSL Certificate Expiry Reminder</h2>";
    $emailBody .= "<p>The following SSL certificates require your attention:</p>";
    $emailBody .= "<table border='1' cellpadding='10' cellspacing='0'>";
    $emailBody .= "<tr><th>Project</th><th>Domain</th><th>Provider</th><th>Expiry Date</th><th>Days Left</th></tr>";

    foreach ($expiringCerts as $cert) {
        $emailBody .= "<tr>";
        $emailBody .= "<td>{$cert['title']}</td>";
        $emailBody .= "<td>{$cert['domain_url']}</td>";
        $emailBody .= "<td>{$cert['provider']}</td>";
        $emailBody .= "<td>{$cert['expiry_date']}</td>";
        $emailBody .= "<td style='color:red; font-weight:bold;'>{$cert['days_left']}</td>";
        $emailBody .= "</tr>";
    }
    $emailBody .= "</table>";
    $emailBody .= "<br><p>Please renew them promptly to avoid service downtime.</p>";

    // ----- SMTP Configuration (Placeholder) -----
    // To actually send the email, you would initialize PHPMailer here.
    /*
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@example.com';
        $mail->Password   = 'your_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@rlabz.in', 'RLabz System');
        $mail->addAddress('admin@rlabz.in', 'RLabz Admin'); // Recipient

        $mail->isHTML(true);
        $mail->Subject = 'URGENT: SSL Certificates Expiring Soon';
        $mail->Body    = $emailBody;

        $mail->send();
        echo "Reminder email sent successfully.\n";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
    }
    */
    
    // For now, since this is a local setup and PHPMailer isn't installed yet,
    // we just log the email body to a file.
    file_put_contents(__DIR__ . '/last_ssl_reminder_log.html', $emailBody);
    echo "Reminder generated! (Saved to last_ssl_reminder_log.html since SMTP is not configured yet).\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
