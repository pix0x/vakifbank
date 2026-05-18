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

echo "\n--- Env Vars ---\n";
echo "VERCEL: " . var_export(getenv('VERCEL'), true) . "\n";
echo "VERCEL_ENV: " . var_export(getenv('VERCEL_ENV'), true) . "\n";
echo "VERCEL_ACCESS_TOKEN: " . var_export(getenv('VERCEL_ACCESS_TOKEN') ? substr(getenv('VERCEL_ACCESS_TOKEN'), 0, 10) . '...' : '', true) . "\n";
echo "VERCEL_BLOB_API_URL: " . var_export(getenv('VERCEL_BLOB_API_URL'), true) . "\n";
echo "BLOB_READ_WRITE_TOKEN: " . var_export(getenv('BLOB_READ_WRITE_TOKEN') ? substr(getenv('BLOB_READ_WRITE_TOKEN'), 0, 15) . '...' : '', true) . "\n";

echo "\n--- Direct API via api.vercel.com (internal) ---\n";
$token = getenv('VERCEL_ACCESS_TOKEN') ?: getenv('BLOB_READ_WRITE_TOKEN') ?: '';
$apiUrl = getenv('VERCEL_BLOB_API_URL') ?: 'https://api.vercel.com/v1/blob';
$path = 'test_internal_' . time() . '.txt';
$url = $apiUrl . '/upload?pathname=' . urlencode($path) . '&allowOverwrite=true';
echo "URL: $url\n";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_PUT => 1,
    CURLOPT_INFILESIZE => 5,
    CURLOPT_INFILE => fopen('data://text/plain,hello', 'r'),
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'x-api-version: 12'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
unset($ch);
echo "HTTP: $http\n";
echo "cURL error: " . var_export($curlErr, true) . "\n";
echo "Response: " . var_export($resp, true) . "\n";

echo "\n--- Direct API via vercel.com/blob (external) ---\n";
$token2 = getenv('BLOB_READ_WRITE_TOKEN') ?: '';
$path2 = 'test_external_' . time() . '.txt';
$url2 = 'https://vercel.com/api/blob/?pathname=' . urlencode($path2) . '&allowOverwrite=true';
echo "URL: $url2\n";
$ch2 = curl_init($url2);
curl_setopt_array($ch2, [
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => 'test external ' . date('Y-m-d H:i:s'),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token2,
        'Content-Type: application/octet-stream',
        'x-api-version: 12',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$resp2 = curl_exec($ch2);
$http2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlErr2 = curl_error($ch2);
unset($ch2);
echo "HTTP: $http2\n";
echo "cURL error: " . var_export($curlErr2, true) . "\n";
echo "Response: " . var_export($resp2, true) . "\n";

echo "\n--- Blob applications.json fetch ---\n";
$blobApps = blobFetch('applications.json');
echo "blobFetch result: " . var_export($blobApps !== null, true) . "\n";
if ($blobApps !== null) {
    $parsed = json_decode($blobApps, true);
    echo "Blob apps count: " . (is_array($parsed) ? count($parsed) : 0) . "\n";
} else {
    echo "applications.json NOT FOUND in blob!\n";
}
