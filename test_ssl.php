<?php
$errno = 0;
$errstr = '';
$ctx = stream_context_create([
    'ssl' => [
        'capture_peer_cert' => true,
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);
$s = @stream_socket_client('ssl://rlabz.in:443', $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
if (!$s) {
    echo "Failed: $errstr ($errno)\n";
} else {
    $params = stream_context_get_params($s);
    $certResource = $params['options']['ssl']['peer_certificate'];
    $cert = openssl_x509_parse($certResource);
    echo "Success! Valid to: " . date('Y-m-d', $cert['validTo_time_t']) . "\n";
}
