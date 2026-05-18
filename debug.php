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
