<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

header('Content-Type: text/plain; charset=utf-8');

$token = blobToken();
$sid = blobStoreId();

echo "Store ID: $sid\n";
echo "Token prefix: " . substr($token, 0, 25) . "...\n\n";

// Try different URL formats
$urls = [
    'public'       => "https://{$sid}.public.blob.vercel-storage.com/test.txt",
    'blob'         => "https://{$sid}.blob.vercel-storage.com/test.txt",
    'private'      => "https://{$sid}.private.blob.vercel-storage.com/test.txt",
];

foreach ($urls as $label => $url) {
    echo "=== Testing URL format: $label ===\n";
    echo "URL: $url\n\n";

    // Test PUT
    echo "--- PUT (with auth) ---\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => 'hello',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/octet-stream',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    unset($ch);

    echo "HTTP: $http\n";
    echo "cURL: " . ($curlErr ?: '(none)') . "\n";
    if ($resp) {
        echo "Headers:\n" . substr($resp, 0, $headerSize) . "\n";
        echo "Body: " . substr($resp, $headerSize);
    }
    echo "\n\n";

    // Test GET with auth
    echo "--- GET (with auth) ---\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    unset($ch);

    echo "HTTP: $http\n";
    $curlErr2 = curl_error($ch);
    echo "cURL: " . ($curlErr2 ?: '(none)') . "\n";
    if ($resp) {
        echo "Headers:\n" . substr($resp, 0, $headerSize) . "\n";
        echo "Body: " . substr($resp, $headerSize);
    }
    echo "\n\n";
}
