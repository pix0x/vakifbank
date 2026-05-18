<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== BLOB STORAGE TEST ===\n\n";

$token = getenv('BLOB_READ_WRITE_TOKEN');
echo "BLOB_READ_WRITE_TOKEN: " . ($token ? substr($token, 0, 20) . '...' : 'NOT SET') . "\n";
echo "Store ID: " . (blobStoreId() ?: 'FAILED') . "\n";
echo "Public URL: " . (blobPublicUrl('test.txt') ?: 'FAILED') . "\n\n";

// Test write
$testData = 'merhaba_blob_' . time();
echo "Writing test data...\n";
$writeOk = blobStore('test.txt', $testData);
echo "Write: " . ($writeOk ? 'OK' : 'FAILED') . "\n\n";

// Test read
echo "Reading test data...\n";
$readData = blobFetch('test.txt');
echo "Read: " . ($readData !== null ? 'OK' : 'FAILED') . "\n";
echo "Value: " . ($readData ?? 'null') . "\n";
echo "Match: " . ($readData === $testData ? 'YES' : 'NO') . "\n\n";

// Test applications storage
echo "=== APPLICATION DATA TEST ===\n\n";
echo "DATA_FILE: " . DATA_FILE . "\n";
echo "File exists: " . (is_file(DATA_FILE) ? 'YES' : 'NO') . "\n\n";

$apps = loadApplications();
echo "Applications count: " . count($apps) . "\n";
if (count($apps) > 0) {
    $last = $apps[count($apps) - 1];
    echo "Last app ID: " . ($last['id'] ?? '-') . "\n";
    echo "Last app user_code: " . ($last['user_code'] ?? '-') . "\n";
}
echo "\nDone.\n";
