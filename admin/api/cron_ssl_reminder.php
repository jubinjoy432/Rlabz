<?php
/**
 * Cron Script for SSL Certificate Expiry Reminders
 */

session_start();
if (php_sapi_name() !== 'cli' && !isset($_GET['token']) && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    die("Access denied.");
}

require_once __DIR__ . '/db.php';

echo "Starting SSL Expiry Check...\n";

function get_ssl_cert_info($domain) {
    $parsed = parse_url($domain);
    $host = isset($parsed['host']) ? $parsed['host'] : $domain;
    $host = preg_replace('#^https?://#', '', $host);
    $host = explode('/', $host)[0];
    
    $context = stream_context_create([
        "ssl" => [
            "capture_peer_cert" => true,
            "verify_peer" => false, 
            "verify_peer_name" => false
        ]
    ]);
    
    $errno = 0;
    $errstr = '';
    $stream = @stream_socket_client("ssl://" . $host . ":443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
    
    if (!$stream) {
        return ['error' => $errstr ?: 'Connection timeout or failed'];
    }
    
    $params = stream_context_get_params($stream);
    $certResource = $params['options']['ssl']['peer_certificate'];
    $cert = openssl_x509_parse($certResource);
    
    $fingerprint = '';
    if (openssl_x509_export($certResource, $certString)) {
        $fingerprint = openssl_x509_fingerprint($certResource, 'sha256');
    }
    
    fclose($stream);
    
    if (!$cert) {
        return ['error' => 'Failed to parse certificate.'];
    }
    $cert['fingerprint'] = $fingerprint;
    return $cert;
}

function add_announcement($pdo, $type, $title, $message, $severity, $ref_id = null) {
    $stmt = $pdo->prepare("INSERT INTO admin_announcements (type, title, message, severity, reference_type, reference_id) VALUES (?, ?, ?, ?, 'ssl_certificate', ?)");
    $stmt->execute([$type, $title, $message, $severity, $ref_id]);
}

try {
    // 1. Remove tracking for projects where ssl_tracking_enabled = 0
    $pdo->exec("DELETE FROM project_ssl_certs WHERE project_id IN (SELECT id FROM projects WHERE ssl_tracking_enabled = 0)");
    
    // 2. Add tracking for projects where ssl_tracking_enabled = 1 and demo_link is valid
    $pdo->exec("INSERT INTO project_ssl_certs (project_id, domain_url)
                SELECT id, demo_link FROM projects 
                WHERE ssl_tracking_enabled = 1 
                AND demo_link IS NOT NULL 
                AND demo_link != '' 
                AND demo_link != '#'
                AND id NOT IN (SELECT project_id FROM project_ssl_certs)");
                
    // 3. Update domains if demo_link changed
    $pdo->exec("UPDATE project_ssl_certs s 
                JOIN projects p ON s.project_id = p.id 
                SET s.domain_url = p.demo_link 
                WHERE p.ssl_tracking_enabled = 1 
                AND s.domain_url != p.demo_link");

    $stmt = $pdo->query("SELECT s.*, p.title as project_title FROM project_ssl_certs s JOIN projects p ON s.project_id = p.id WHERE s.domain_url IS NOT NULL AND s.domain_url != ''");
    $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare("UPDATE project_ssl_certs SET 
        provider = ?, issue_date = ?, expiry_date = ?, certificate_fingerprint = ?, last_checked_at = NOW(), 
        status = ?, last_error = ?, last_alert_level = ? WHERE id = ?");
        
    $failUpdateStmt = $pdo->prepare("UPDATE project_ssl_certs SET 
        last_checked_at = NOW(), status = ?, last_error = ?, last_alert_level = ? WHERE id = ?");

    foreach ($certs as $cert) {
        echo "Checking {$cert['domain_url']}...\n";
        $certInfo = get_ssl_cert_info($cert['domain_url']);
        
        $last_alert_level = $cert['last_alert_level'];
        
        if (isset($certInfo['error'])) {
            $status = 'Check Failed';
            $last_error = $certInfo['error'];
            
            if ($last_alert_level !== 'Failed') {
                add_announcement($pdo, 'ssl_failed', "{$cert['project_title']} SSL Check Failed", "{$cert['domain_url']}\nReason: $last_error", 'danger', $cert['id']);
                $last_alert_level = 'Failed';
            }
            
            $failUpdateStmt->execute([$status, $last_error, $last_alert_level, $cert['id']]);
            echo " -> Failed: $last_error\n";
            continue; 
        }
        // Successful check, auto-resolve any previous failure announcements
        $pdo->prepare("UPDATE admin_announcements SET is_read = 1 WHERE reference_type = 'ssl_certificate' AND reference_id = ? AND type = 'ssl_failed'")->execute([$cert['id']]);

        $validFrom = date('Y-m-d', $certInfo['validFrom_time_t']);
        $validTo = date('Y-m-d', $certInfo['validTo_time_t']);
        $provider = $certInfo['issuer']['O'] ?? ($certInfo['issuer']['CN'] ?? 'Unknown Provider');
        $new_fingerprint = $certInfo['fingerprint'];
        
        $days_left = floor(($certInfo['validTo_time_t'] - time()) / (60 * 60 * 24));
        $status = 'Healthy';
        $last_error = null;
        
        // Detect baseline or renewal
        if (empty($cert['certificate_fingerprint'])) {
            // Baseline establishment, no announcement, reset alert level
            $last_alert_level = null;
        } elseif ($cert['certificate_fingerprint'] !== $new_fingerprint) {
            // RENEWED!
            $last_alert_level = null;
            add_announcement($pdo, 'ssl_renewed', "{$cert['project_title']} SSL Renewed", "{$cert['domain_url']}\nThe SSL certificate has been successfully renewed.\nNew expiry: $validTo\nValidity: " . floor(($certInfo['validTo_time_t'] - $certInfo['validFrom_time_t']) / (60*60*24)) . " days\nDetected: " . date('d F Y'), 'success', $cert['id']);
        }
        
        // Check thresholds
        if ($days_left <= 0) {
            $status = 'Expired';
            if ($last_alert_level !== 'Expired') {
                add_announcement($pdo, 'ssl_expired', "{$cert['project_title']} SSL Expired", "{$cert['domain_url']}\nThis certificate expired on $validTo.", 'danger', $cert['id']);
                $last_alert_level = 'Expired';
            }
        } elseif ($days_left <= 7) {
            $status = 'Critical';
            if (!in_array($last_alert_level, ['Expired', '7_days'])) {
                add_announcement($pdo, 'ssl_critical', "{$cert['project_title']} SSL Critical", "{$cert['domain_url']}\nCritical: Expires in $days_left days ($validTo).", 'danger', $cert['id']);
                $last_alert_level = '7_days';
            }
        } elseif ($days_left <= 14) {
            $status = 'Warning';
            if (!in_array($last_alert_level, ['Expired', '7_days', '14_days'])) {
                add_announcement($pdo, 'ssl_warning', "{$cert['project_title']} SSL Expiring", "{$cert['domain_url']}\nWarning: Expires in $days_left days ($validTo).", 'warning', $cert['id']);
                $last_alert_level = '14_days';
            }
        } elseif ($days_left <= 30) {
            $status = 'Expiring Soon';
            if (!in_array($last_alert_level, ['Expired', '7_days', '14_days', '30_days'])) {
                add_announcement($pdo, 'ssl_notice', "{$cert['project_title']} SSL Expiring Soon", "{$cert['domain_url']}\nNotice: Expires in $days_left days ($validTo).", 'warning', $cert['id']);
                $last_alert_level = '30_days';
            }
        } else {
            // Healthy and > 30 days.
            $status = 'Healthy';
            if (in_array($last_alert_level, ['Expired', '7_days', '14_days', '30_days', 'Failed'])) {
                $last_alert_level = null;
            }
        }

        $updateStmt->execute([
            $provider, $validFrom, $validTo, $new_fingerprint, $status, $last_error, $last_alert_level, $cert['id']
        ]);
        
        echo " -> Success: Expires $validTo ($days_left days left). Status: $status\n";
    }

    echo "Completed SSL Expiry Check.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
