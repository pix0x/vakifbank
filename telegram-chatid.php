<?php
declare(strict_types=1);
$botToken = '8543060508:AAGHfDEY3NxCDyHmfJ_HAY1_uGCinI_syPY';

$url = "https://api.telegram.org/bot{$botToken}/getUpdates";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

$data = json_decode($resp, true);
$updates = $data['result'] ?? [];
?>
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><title>Telegram Chat ID</title>
<style>
  body { background:#111; color:#fff; font-family:monospace; padding:20px; }
  pre { background:#222; padding:10px; border-radius:8px; overflow-x:auto; }
  .id-box { background:#2a2; padding:10px; border-radius:8px; margin:10px 0; font-size:1.2rem; text-align:center; }
  .note { color:#aaa; font-size:0.9rem; }
  code { background:#333; padding:2px 6px; border-radius:4px; }
</style>
</head>
<body>
<h1>Telegram Chat ID Bulma</h1>
<p class="note">1. Telegram'da <code>@<?= htmlspecialchars($botToken, ENT_QUOTES, 'UTF-8') ?></code> botuna mesaj atın<br>
2. Bu sayfayı yenileyin<br>
3. Aşağıdaki chat ID'yi Vercel ortam değişkeni <code>TELEGRAM_CHAT_ID</code> olarak ekleyin</p>

<?php if ($http !== 200): ?>
<p style="color:red">API hatası: HTTP <?= $http ?></p>
<?php elseif (empty($updates)): ?>
<p style="color:orange">Henuz mesaj yok. Bota /start yollayip sayfayi yenileyin.</p>
<?php else: ?>
  <?php foreach (array_reverse($updates) as $u): ?>
    <?php $chat = $u['message']['chat'] ?? $u['callback_query']['message']['chat'] ?? []; if (empty($chat)) continue; ?>
    <div class="id-box">
      Chat ID: <strong><?= htmlspecialchars((string)($chat['id'] ?? '?'), ENT_QUOTES, 'UTF-8') ?></strong>
      (<?= htmlspecialchars($chat['type'] ?? '?', ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($chat['first_name'] ?? $chat['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<hr style="margin:30px 0;border-color:#333">
<h2>Yanitlar (ham JSON)</h2>
<pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
</body>
</html>
