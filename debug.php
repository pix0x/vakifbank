<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

header('Content-Type: text/plain; charset=utf-8');

echo "DATA_FILE: " . DATA_FILE . "\n";
echo "is_writable: " . var_export(is_writable(dirname(DATA_FILE)), true) . "\n";
echo "file_exists: " . var_export(is_file(DATA_FILE), true) . "\n";

if (is_file(DATA_FILE)) {
    echo "File size: " . filesize(DATA_FILE) . "\n";
    echo "Content: " . file_get_contents(DATA_FILE) . "\n";
} else {
    echo "File does not exist.\n";
    echo "Trying to write test file...\n";
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created dir: " . var_export(true, true) . "\n";
    }
    $written = file_put_contents(DATA_FILE, '[]');
    echo "Write result: " . var_export($written, true) . "\n";
    if ($written !== false) {
        echo "Now file_exists: " . var_export(is_file(DATA_FILE), true) . "\n";
        echo "Content: " . file_get_contents(DATA_FILE) . "\n";
    }
}

echo "\n\n--- Applications ---\n";
$apps = loadApplications();
echo "Count: " . count($apps) . "\n";
foreach ($apps as $app) {
    echo "  ID: " . ($app['id'] ?? '?') . " | Status: " . ($app['status'] ?? '?') . " | TC: " . ($app['national_id'] ?? '?') . "\n";
}

echo "\n--- BlobStore() Function Test ---\n";
$bsResult = blobStore('bs_test_' . time() . '.txt', 'blobStore test ' . date('Y-m-d H:i:s'));
echo "blobStore() returned: " . var_export($bsResult, true) . "\n";
echo "Now reading back: " . var_export(blobFetch('bs_test_*.txt'), true) . "\n";
// Note: blobFetch doesn't support glob, so this might not work

echo "\n--- Direct API Similar to writeData ---\n";
$token = blobToken();
$json = json_encode([['id' => 'test_'.time(), 'status' => 'beklemede']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo "Data length: " . strlen($json) . "\n";

// Replicate blobStore exactly
$path = 'write_test_' . time() . '.json';
$url = 'https://vercel.com/api/blob/?pathname=' . urlencode($path) . '&allowOverwrite=true';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => $json,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/octet-stream',
        'x-api-version: 12',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HEADER => false,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
$info = curl_getinfo($ch);
unset($ch);
echo "HTTP: $http\n";
echo "cURL error: " . var_export($curlErr, true) . "\n";
echo "Response: " . var_export($resp, true) . "\n";
echo "Total time: " . ($info['total_time'] ?? '?') . "\n";
echo "Redirect count: " . ($info['redirect_count'] ?? '?') . "\n";
echo "\nInput JSON length for this test: " . strlen($json) . "\n";

echo "\n--- Blob applications.json fetch ---\n";
$blobApps = blobFetch('applications.json');
echo "blobFetch result: " . var_export($blobApps !== null, true) . "\n";
if ($blobApps !== null) {
    $parsed = json_decode($blobApps, true);
    echo "Blob apps count: " . (is_array($parsed) ? count($parsed) : 0) . "\n";
} else {
    echo "applications.json NOT FOUND in blob!\n";
}
