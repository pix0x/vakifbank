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

echo "\n--- Blob Store Test ---\n";
$testWrite = blobStore('debug_test.txt', 'test ' . date('Y-m-d H:i:s'));
echo "blobStore write: " . var_export($testWrite, true) . "\n";

$testRead = blobFetch('debug_test.txt');
echo "blobFetch read: " . var_export($testRead, true) . "\n";

echo "\n--- Blob applications.json fetch ---\n";
$blobApps = blobFetch('applications.json');
echo "blobFetch result: " . var_export($blobApps !== null, true) . "\n";
if ($blobApps !== null) {
    $parsed = json_decode($blobApps, true);
    echo "Blob apps count: " . (is_array($parsed) ? count($parsed) : 0) . "\n";
} else {
    echo "applications.json NOT FOUND in blob!\n";
}

echo "\n--- Direct API Write Test ---\n";
$token = blobToken();
$apiUrl = 'https://vercel.com/api/blob/?pathname=direct_test.txt&allowOverwrite=true';
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_POSTFIELDS => 'direct test ' . date('Y-m-d H:i:s'),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/octet-stream',
        'x-api-version: 12',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);
echo "Direct API HTTP: $http\n";
echo "Direct API response: " . var_export($resp, true) . "\n";
