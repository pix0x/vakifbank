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
    'public'  => "https://{$sid}.public.blob.vercel-storage.com/test.txt",
    'private' => "https://{$sid}.blob.vercel-storage.com/test.txt",
    'v2'      => "https://{$sid}.blob.vercel-storage.com/test.txt?public=true",
];

foreach ($urls as $label => $url) {
    echo "=== Testing URL format: $label ===\n";
    echo "URL: $url\n\n";

    // Test PUT
    echo "--- PUT ---\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => 'hello',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/octet-stream',
            'Content-Length: 5',
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
    echo "Headers:\n" . substr($resp, 0, $headerSize) . "\n";
    echo "Body: " . substr($resp, $headerSize) . "\n\n";

    // Test GET
    echo "--- GET ---\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    unset($ch);

    echo "HTTP: $http\n";
    echo "Headers:\n" . substr($resp, 0, $headerSize) . "\n";
    echo "Body: " . substr($resp, $headerSize) . "\n\n";
}
