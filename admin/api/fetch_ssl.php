<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['domain']) || empty(trim($_GET['domain']))) {
    echo json_encode(['error' => 'Domain is required']);
    exit;
}

$domain = trim($_GET['domain']);

// Clean up domain (remove http://, https://, paths)
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
    echo json_encode(['error' => 'Unable to retrieve certificate. Reason: ' . ($errstr ?: 'Connection timed out.')]);
    exit;
}

$params = stream_context_get_params($stream);
$certResource = $params['options']['ssl']['peer_certificate'];
$cert = openssl_x509_parse($certResource);

// Compute fingerprint
$fingerprint = '';
if (openssl_x509_export($certResource, $certString)) {
    $fingerprint = openssl_x509_fingerprint($certResource, 'sha256');
}

fclose($stream);

if (!$cert) {
    echo json_encode(['error' => 'Failed to parse certificate.']);
    exit;
}

$validFrom = date('Y-m-d', $cert['validFrom_time_t']);
$validTo = date('Y-m-d', $cert['validTo_time_t']);
$provider = $cert['issuer']['O'] ?? ($cert['issuer']['CN'] ?? 'Unknown Provider');

$days_left = floor(($cert['validTo_time_t'] - time()) / (60 * 60 * 24));

$status = 'Valid';
if ($days_left <= 0) {
    $status = 'Expired';
}

echo json_encode([
    'success' => true,
    'provider' => $provider,
    'issue_date' => $validFrom,
    'expiry_date' => $validTo,
    'days_left' => $days_left,
    'status' => $status,
    'fingerprint' => $fingerprint
]);
