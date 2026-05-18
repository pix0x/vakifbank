<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

$key = getenv('ADMIN_KEY') ?: '';
if ($key === '' || (string) ($_GET['key'] ?? '') !== $key) {
    http_response_code(403);
    echo 'yetkisiz';
    exit;
}

$id = trim((string) ($_GET['id'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
if ($id === '' || $status === '') {
    echo 'id ve status gerekli';
    exit;
}

$ok = updateApplicationStatus($id, $status);
echo $ok ? 'tamam' : 'basarisiz';
