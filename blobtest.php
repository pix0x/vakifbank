<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== BLOB STORAGE DEBUG ===\n\n";

echo "VERCEL env: " . var_export((bool)getenv('VERCEL'), true) . "\n";
echo "VERCEL_URL env: " . var_export(getenv('VERCEL_URL'), true) . "\n";
echo "BLOB_READ_WRITE_TOKEN set: " . var_export(blobToken() !== '', true) . "\n";
echo "Token length: " . strlen(blobToken()) . "\n";
echo "Token prefix: " . substr(blobToken(), 0, 20) . "...\n\n";

echo "DATA_FILE: " . DATA_FILE . "\n";
echo "PRESENCE_FILE: " . PRESENCE_FILE . "\n\n";

echo "Local DATA_FILE exists: " . var_export(is_file(DATA_FILE), true) . "\n";
echo "Local PRESENCE_FILE exists: " . var_export(is_file(PRESENCE_FILE), true) . "\n\n";

if (is_file(DATA_FILE)) {
    $local = @file_get_contents(DATA_FILE);
    $parsed = json_decode($local, true);
    echo "Local applications count: " . (is_array($parsed) ? count($parsed) : 0) . "\n";
}

echo "\n--- Testing Blob Store ID Extract ---\n";
$sid = blobStoreId();
echo "Store ID: " . ($sid ?: '(empty - EXTRACTION FAILED)') . "\n";

if ($sid !== '') {
    echo "\n--- Testing Blob Public URL ---\n";
    $url = blobPublicUrl('applications.json');
    echo "Public URL: " . $url . "\n";

    echo "\n--- Testing Blob Fetch (applications.json) ---\n";
    $blob = blobFetch('applications.json');
    echo "Blob fetch result: " . var_export($blob !== null, true) . "\n";
    if ($blob !== null) {
        $parsed = json_decode($blob, true);
        echo "Blob applications count: " . (is_array($parsed) ? count($parsed) : 0) . "\n";
    } else {
        echo "Blob is null/empty (no data yet - normal if this is a fresh store)\n";
    }

    echo "\n--- Testing Blob Store (write) ---\n";
    $testUrl = blobPublicUrl('test.txt');
    echo "Target URL: $testUrl\n";

    $ch = curl_init($testUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => 'hello world ' . date('Y-m-d H:i:s'),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . blobToken(),
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => true,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    $curlErrNo = curl_errno($ch);
    unset($ch);

    echo "HTTP Status: " . var_export($http, true) . "\n";
    echo "cURL error: " . var_export($curlErr, true) . "\n";
    echo "cURL errno: " . var_export($curlErrNo, true) . "\n";
    echo "Response: " . var_export($resp, true) . "\n";
    echo "Blob store test result: " . var_export($http >= 200 && $http < 300, true) . "\n";

    echo "\n--- Testing Blob Fetch (test.txt - verify write) ---\n";
    $testRead = blobFetch('test.txt');
    echo "Blob readback: " . var_export($testRead, true) . "\n";
    echo "Match: " . var_export($testRead === 'hello world ' . date('Y-m-d H:i:s'), true) . "\n";
}

echo "\n=== DEBUG END ===\n";
