<?php
$domains = ['https://icswhmh.com/', 'https://dyuti.in/rcss/'];
foreach ($domains as $domain) {
    echo "Testing domain: $domain\n";
    $parsed = parse_url($domain);
    $host = isset($parsed['host']) ? $parsed['host'] : $domain;
    $host = preg_replace('#^https?://#', '', $host);
    $host = explode('/', $host)[0];
    echo "Resolved host: $host\n";
    
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
        echo "Error: $errstr\n\n";
        continue;
    }
    
    $params = stream_context_get_params($stream);
    $certResource = $params['options']['ssl']['peer_certificate'];
    $cert = openssl_x509_parse($certResource);
    
    $fingerprint = '';
    if (openssl_x509_export($certResource, $certString)) {
        $fingerprint = openssl_x509_fingerprint($certResource, 'sha256');
    }
    fclose($stream);
    
    $validFrom = date('Y-m-d', $cert['validFrom_time_t']);
    $validTo = date('Y-m-d', $cert['validTo_time_t']);
    $provider = $cert['issuer']['O'] ?? ($cert['issuer']['CN'] ?? 'Unknown Provider');
    $days_left = floor(($cert['validTo_time_t'] - time()) / (60 * 60 * 24));
    
    echo "Provider: $provider\n";
    echo "Issue Date: $validFrom\n";
    echo "Expiry Date: $validTo\n";
    echo "Days Left: $days_left\n";
    echo "Fingerprint: $fingerprint\n";
    echo "Subject: " . json_encode($cert['subject']) . "\n";
    echo "----------------------------------------\n";
}
