<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

const TELEGRAM_API = 'https://api.telegram.org/bot';
const VERCEL_API   = 'https://api.vercel.com';

// Tüm tokenlar environment variable'dan alinir. Vercel Dashboard -> Environment Variables'a ekleyin:
//   TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID, VERCEL_TOKEN, VERCEL_PROJECT_ID, VERCEL_TEAM_ID
//   CF_ACCOUNT_ID, CF_KV_ID, CF_API_TOKEN, GOOGLE_API_KEY
$_ENV['TELEGRAM_BOT_TOKEN']  = getenv('TELEGRAM_BOT_TOKEN') ?: '';
$_ENV['TELEGRAM_CHAT_ID']    = getenv('TELEGRAM_CHAT_ID') ?: '';
$_ENV['VERCEL_TOKEN']        = getenv('VERCEL_TOKEN') ?: '';
$_ENV['VERCEL_PROJECT_ID']   = getenv('VERCEL_PROJECT_ID') ?: '';
$_ENV['VERCEL_TEAM_ID']      = getenv('VERCEL_TEAM_ID') ?: '';
$_ENV['CF_ACCOUNT_ID']       = getenv('CF_ACCOUNT_ID') ?: '';
$_ENV['CF_KV_ID']            = getenv('CF_KV_ID') ?: '';
$_ENV['CF_API_TOKEN']        = getenv('CF_API_TOKEN') ?: '';
$_ENV['GOOGLE_API_KEY']      = getenv('GOOGLE_API_KEY') ?: '';

const STATE_FILE = __DIR__ . '/current_domain.txt';

function tgSend(int $chatId, string $text, string $parseMode = 'HTML'): array
{
    $token = $_ENV['TELEGRAM_BOT_TOKEN'];
    if (!$token) return ['ok' => false, 'error' => 'TELEGRAM_BOT_TOKEN not set'];

    $url = TELEGRAM_API . $token . '/sendMessage';
    $data = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => $parseMode,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    return json_decode($resp, true) ?: ['ok' => false, 'http_code' => $httpCode];
}

function vercelApi(string $method, string $path, ?array $body = null): array
{
    $token = $_ENV['VERCEL_TOKEN'];
    if (!$token) return ['ok' => false, 'error' => 'VERCEL_TOKEN not set'];

    $teamId = $_ENV['VERCEL_TEAM_ID'];
    $query = $teamId ? '?teamId=' . urlencode($teamId) : '';

    $url = VERCEL_API . $path . $query;

    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        if ($body) $options[CURLOPT_POSTFIELDS] = json_encode($body);
    } elseif ($method === 'DELETE') {
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        if ($body) $options[CURLOPT_POSTFIELDS] = json_encode($body);
    } elseif ($method === 'GET') {
        $options[CURLOPT_HTTPGET] = true;
    }

    curl_setopt_array($ch, $options);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    return ['ok' => $httpCode >= 200 && $httpCode < 300, 'http_code' => $httpCode, 'data' => json_decode($resp, true)];
}

function cfApiPut(string $key, string $value): int
{
    $url = "https://api.cloudflare.com/client/v4/accounts/{$_ENV['CF_ACCOUNT_ID']}/storage/kv/namespaces/{$_ENV['CF_KV_ID']}/values/" . urlencode($key);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS    => $value,
        CURLOPT_HTTPHEADER    => [
            'Authorization: Bearer ' . $_ENV['CF_API_TOKEN'],
            'Content-Type: text/plain',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT       => 15,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    return $code;
}

function isDomainFlagged(string $url): bool
{
    $apiKey = $_ENV['GOOGLE_API_KEY'];
    $apiUrl = "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=" . $apiKey;
    $data = [
        "client" => ["clientId" => "uyap-auto-rotate", "clientVersion" => "1.0.0"],
        "threatInfo" => [
            "threatTypes"      => ["MALWARE", "SOCIAL_ENGINEERING", "UNWANTED_SOFTWARE", "POTENTIALLY_HARMFUL_APPLICATION"],
            "platformTypes"    => ["ANY_PLATFORM"],
            "threatEntryTypes" => ["URL"],
            "threatEntries"    => [["url" => $url]]
        ]
    ];
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    unset($ch);
    $res = json_decode($response, true);
    return isset($res['matches']) && count($res['matches']) > 0;
}

function getCurrentDomain(): string
{
    return file_exists(STATE_FILE) ? trim(file_get_contents(STATE_FILE)) : '';
}

function handleCommand(int $chatId, string $cmd, array $args): void
{
    switch ($cmd) {
        case '/start':
        case '/help':
            $msg = "🤖 <b>VakıfBank Domain Bot</b>\n\n"
                 . "<b>/domain</b> <code>example.com</code> — Yeni domain ekle\n"
                 . "<b>/list</b> — Domainleri listele\n"
                 . "<b>/remove</b> <code>example.com</code> — Domain sil\n"
                 . "<b>/rotate</b> — Yeni deploy tetikle\n"
                 . "<b>/yeni</b> — Vercel + Cloudflare domain döndürme\n"
                 . "<b>/durum</b> — Mevcut domain sağlık durumu\n"
                 . "<b>/help</b> — Yardım";
            tgSend($chatId, $msg);
            break;

        case '/domain':
            if (empty($args)) {
                tgSend($chatId, "❌ Kullanım: <code>/domain example.com</code>");
                return;
            }
            $domain = trim($args[0]);
            $projectId = $_ENV['VERCEL_PROJECT_ID'];
            if (!$projectId) {
                tgSend($chatId, "❌ VERCEL_PROJECT_ID ayarlanmamış.");
                return;
            }
            $result = vercelApi('POST', "/v9/projects/{$projectId}/domains", ['name' => $domain]);
            if ($result['ok']) {
                tgSend($chatId, "✅ Domain <b>{$domain}</b> başarıyla eklendi!\n\n"
                     . "📌 DNS ayarlarını Vercel'in verdiği hedefe yönlendirmeyi unutma.");
            } else {
                $err = $result['data']['error']['message'] ?? json_encode($result['data']);
                tgSend($chatId, "❌ Domain eklenemedi: {$err}");
            }
            break;

        case '/list':
            $projectId = $_ENV['VERCEL_PROJECT_ID'];
            if (!$projectId) {
                tgSend($chatId, "❌ VERCEL_PROJECT_ID ayarlanmamış.");
                return;
            }
            $result = vercelApi('GET', "/v9/projects/{$projectId}/domains");
            if (!$result['ok']) {
                tgSend($chatId, "❌ Domainler alınamadı.");
                return;
            }
            $domains = $result['data']['domains'] ?? [];
            if (empty($domains)) {
                tgSend($chatId, "📭 Henüz domain eklenmemiş.");
                return;
            }
            $lines = [];
            foreach ($domains as $d) {
                $name = $d['name'] ?? '-';
                $verified = $d['verified'] ? '✅' : '⏳';
                $lines[] = "{$verified} <b>{$name}</b>";
            }
            $count = count($domains);
            tgSend($chatId, "📋 <b>Domainler ({$count})</b>:\n" . implode("\n", $lines));
            break;

        case '/remove':
            if (empty($args)) {
                tgSend($chatId, "❌ Kullanım: <code>/remove example.com</code>");
                return;
            }
            $domain = trim($args[0]);
            $projectId = $_ENV['VERCEL_PROJECT_ID'];
            if (!$projectId) {
                tgSend($chatId, "❌ VERCEL_PROJECT_ID ayarlanmamış.");
                return;
            }
            $result = vercelApi('DELETE', "/v9/projects/{$projectId}/domains/{$domain}");
            if ($result['ok']) {
                tgSend($chatId, "✅ Domain <b>{$domain}</b> kaldırıldı.");
            } else {
                $err = $result['data']['error']['message'] ?? 'Bilinmeyen hata';
                tgSend($chatId, "❌ Domain kaldırılamadı: {$err}");
            }
            break;

        case '/rotate':
            $projectId = $_ENV['VERCEL_PROJECT_ID'];
            $token = $_ENV['VERCEL_TOKEN'];
            if (!$projectId || !$token) {
                tgSend($chatId, "❌ VERCEL_PROJECT_ID veya VERCEL_TOKEN ayarlanmamış.");
                return;
            }
            tgSend($chatId, "🔄 Yeni deploy tetikleniyor...");
            $teamId = $_ENV['VERCEL_TEAM_ID'];
            $query = $teamId ? '?teamId=' . urlencode($teamId) : '';
            $ch = curl_init(VERCEL_API . "/v13/deployments{$query}");
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'name'                 => 'vakifbank',
                    'project'              => $projectId,
                    'target'               => 'production',
                    'withLatestSources'    => true,
                ]),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            unset($ch);
            $deployData = json_decode($resp, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                $url = $deployData['url'] ?? 'bilinmiyor';
                $alias = $deployData['alias'] ?? null;
                $msg = "✅ Yeni deploy oluşturuldu!\n🌐 <code>{$url}</code>";
                if ($alias) $msg .= "\n🔗 Alias: <code>" . implode(', ', $alias) . '</code>';
                tgSend($chatId, $msg);
                $listRes = vercelApi('GET', "/v9/projects/{$projectId}/domains");
                $domains = $listRes['data']['domains'] ?? [];
                foreach ($domains as $d) {
                    $name = $d['name'] ?? '';
                    if ($name && !str_contains($name, '.vercel.app')) {
                        vercelApi('DELETE', "/v9/projects/{$projectId}/domains/{$name}");
                    }
                }
            } else {
                $err = $deployData['error']['message'] ?? 'Deploy başarısız';
                tgSend($chatId, "❌ Deploy hatası: {$err}");
            }
            break;

        case '/yeni':
            $projectId = $_ENV['VERCEL_PROJECT_ID'];
            $token = $_ENV['VERCEL_TOKEN'];
            if (!$projectId || !$token) {
                tgSend($chatId, "❌ VERCEL_PROJECT_ID veya VERCEL_TOKEN ayarlanmamış.");
                return;
            }
            tgSend($chatId, "⏳ İşlem başlatıldı...\n1️⃣ Vercel'de yeni domain oluşturuluyor...");

            $newDomain = "portal-uyap-" . date('His') . ".vercel.app";
            $newTarget = "https://" . $newDomain;

            $result = vercelApi('POST', "/v9/projects/{$projectId}/domains", ['name' => $newDomain]);
            if (!$result['ok']) {
                $err = $result['data']['error']['message'] ?? json_encode($result['data']);
                tgSend($chatId, "❌ Vercel domain eklenemedi: {$err}");
                return;
            }
            tgSend($chatId, "✅ 1/3: Vercel domain <b>{$newDomain}</b> eklendi.\n2️⃣ Cloudflare KV güncelleniyor...");

            $cfCode = cfApiPut('TARGET_URL', $newTarget);
            if ($cfCode !== 200) {
                tgSend($chatId, "❌ Cloudflare KV güncellenemedi! HTTP: {$cfCode}");
                return;
            }
            tgSend($chatId, "✅ 2/3: Cloudflare güncellendi.\n3️⃣ Eski domain temizleniyor...");

            $oldDomain = getCurrentDomain();
            if ($oldDomain && $oldDomain !== $newDomain) {
                vercelApi('DELETE', "/v9/projects/{$projectId}/domains/{$oldDomain}");
            }
            file_put_contents(STATE_FILE, $newDomain);

            tgSend($chatId, "✅ 3/3: Temizlik tamamlandı.\n\n🚀 <b>İŞLEM TAMAMLANDI</b>\n\n🛡️ Cloaking: AKTİF\n🌍 Yeni Hedef: <code>{$newDomain}</code>\n🗑️ Silinen: <code>" . ($oldDomain ?: 'yok') . "</code>");
            break;

        case '/durum':
        case '/status':
            $currentDomain = getCurrentDomain();
            if (!$currentDomain) {
                tgSend($chatId, "ℹ️ Henüz bir domain döndürme işlemi yapılmamış. <code>/yeni</code> ile başlatın.");
                return;
            }
            tgSend($chatId, "🔍 Anlık API kontrolü yapılıyor: <b>{$currentDomain}</b> ...");
            $isFlagged = isDomainFlagged("https://" . $currentDomain);
            $status = $isFlagged ? "❌ KIRMIZI (Patlamış)" : "✅ TEMİZ (Aktif)";
            $msg = "📊 <b>ANLIK SİSTEM DURUMU</b>\n\n"
                 . "🌍 Mevcut Domain: <code>{$currentDomain}</code>\n"
                 . "🛡️ Sağlık Durumu: {$status}\n\n"
                 . "⚙️ Cloudflare Yönlendirmesi: Aktif";
            tgSend($chatId, $msg);
            break;

        default:
            tgSend($chatId, "❌ Bilinmeyen komut. /help yaz.");
    }
}

// --- Webhook Setup (GET isteği ile) ---
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['action']) && $_GET['action'] === 'setwebhook') {
    $token = $_ENV['TELEGRAM_BOT_TOKEN'];
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/telegram.php';
    $url = TELEGRAM_API . $token . "/setWebhook?url=" . urlencode($baseUrl);
    $resp = file_get_contents($url);
    echo "<pre>Webhook Kurulum Sonucu:\n" . print_r(json_decode($resp, true), true) . "</pre>";
    exit;
}

// --- Webhook Entry ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    http_response_code(200);
    echo 'ok';
    exit;
}

$msg  = $input['message'];
$chatId = (int) ($msg['chat']['id'] ?? 0);
$text = trim((string) ($msg['text'] ?? ''));

$allowedChatId = (int) ($_ENV['TELEGRAM_CHAT_ID'] ?? 0);
if ($chatId <= 0 || $text === '' || ($allowedChatId && $chatId !== $allowedChatId)) {
    http_response_code(200);
    echo 'ok';
    exit;
}

$parts = preg_split('/\s+/', $text);
$cmd   = $parts[0] ?? '';
$args  = array_slice($parts, 1);

handleCommand($chatId, $cmd, $args);

http_response_code(200);
echo 'ok';
