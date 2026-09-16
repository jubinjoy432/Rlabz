<?php
$domains = ['icswhmh.com', 'dyuti.in/rcss', 'https://icswhmh.com/', 'http://dyuti.in/rcss/'];
foreach ($domains as $domain) {
    echo "Testing input: '$domain'\n";
    $parsed = parse_url($domain);
    $host = isset($parsed['host']) ? $parsed['host'] : $domain;
    $host = preg_replace('#^https?://#', '', $host);
    $host = explode('/', $host)[0];
    echo "Resolved host: '$host'\n";
}
