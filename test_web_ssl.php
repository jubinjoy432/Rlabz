<?php
$domain = "https://rlabz.in";
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
// Remove @ to see real error
$stream = stream_socket_client("ssl://" . $host . ":443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

if (!$stream) {
    echo json_encode(['error' => $errstr ?: 'Connection timeout or failed', 'err_no' => $errno]);
} else {
    echo json_encode(['success' => true]);
}
