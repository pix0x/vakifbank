<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

header('Content-Type: text/plain; charset=utf-8');

$token = blobToken();
$sid = blobStoreId();

echo "Store ID: $sid\n";
echo "Token prefix: " . substr($token, 0, 25) . "...\n\n";

// Write test via proper Vercel Blob API
echo "=== WRITE via API (vercel.com/api/blob) ===\n";
$apiUrl = 'https://vercel.com/api/blob/?pathname=test.txt';
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => 'hello world',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/octet-stream',
        'x-api-version: 12',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HEADER => true,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlErr = curl_error($ch);
unset($ch);

echo "HTTP: $http\n";
echo "cURL: " . ($curlErr ?: '(none)') . "\n";
echo "Body: " . substr($resp, $headerSize) . "\n\n";

// Read test via public URL
echo "=== READ via Public URL ===\n";
$publicUrl = blobPublicUrl('test.txt');
echo "URL: $publicUrl\n";
$ch = curl_init($publicUrl);
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
echo "Body: " . substr($resp, $headerSize) . "\n";
