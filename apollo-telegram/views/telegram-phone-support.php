<?php
if (! defined('ABSPATH')) {
  exit;
}
$apollo_telegram_page_config = $apollo_telegram_page_config ?? array(
  'restUrl' => rest_url('apollo-telegram/v1'),
  'nonce'   => wp_create_nonce('apollo_telegram_verify'),
  'botUser' => function_exists('apollo_telegram_bot_username') ? apollo_telegram_bot_username() : '',
);
// CSP nonce: apollo-login sends a strict script-src ('nonce-…' + strict-dynamic).
// Standalone views bypass script_loader_tag, so the nonce must be inlined here.
$apollo_telegram_csp_nonce = isset($GLOBALS['apollo_csp_nonce']) ? (string) $GLOBALS['apollo_csp_nonce'] : '';
$apollo_telegram_nonce_attr = '' !== $apollo_telegram_csp_nonce ? ' nonce="' . esc_attr($apollo_telegram_csp_nonce) . '"' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
  <meta charset="UTF-8">
  <link rel="preconnect" href="https://assets.apollo.rio.br">
  <link rel="preconnect" href="https://cdn.apollo.rio.br">
  <!--
  Mandatory Apollo CORE.JS: global standard of theme and design to all apollo pages!
  -->
  <script<?php echo $apollo_telegram_nonce_attr; ?> id="apollo-core-js" src="https://cdn.apollo.rio.br/v1.0.0/core.js?prod=true&versao=bb" fetchpriority="high"></script>
  <style id="apollo-page-tokens">
  :root {
  /* page-local extras only — tokens come from core.js #cdn-apollo */
  }
  </style>
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover, interactive-widget=overlays-content">
  <title>Apollo Phone Verification</title>

  <script<?php echo $apollo_telegram_nonce_attr; ?>>
    window.apolloTelegramConfig = <?php echo wp_json_encode($apollo_telegram_page_config); ?>;
  </script>

  <style>
    :root {
      --dropdown-max-height: 280px;
    }

    body {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: var(--s-4);
      overflow-x: hidden;
      font-family: var(--ff-main, system-ui, sans-serif);
    }

        .main-container {
          width: 100%;
          max-width: 480px;
          display: flex;
          flex-direction: column;
          gap: var(--s-4);
          z-index: var(--z-base);
        }

        .header-text {
          text-align: center;
          margin-bottom: var(--s-2);
        }

        .phone-group {
          position: relative;
          display: flex;
          align-items: center;
          background: var(--surface);
          border-radius: var(--r-pill);
          box-shadow: var(--ins-shadow);
          padding: 6px;
          transition: background var(--t-ui), box-shadow var(--t-ui);
          z-index: 10;
        }

        .phone-group:focus-within {
          background: var(--card);
          box-shadow: var(--ins-shadow), inset 0 0 0 1px var(--white-10);
        }

        .flag-selector {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 8px 12px;
          border-radius: var(--r-pill);
          background: var(--surface-hover);
          cursor: pointer;
          flex-shrink: 0;
          transition: background var(--t-ui);
        }

        .flag-selector:hover {
          background: var(--white-5);
        }

        .flag-icon {
          width: 22px;
          height: 16px;
          border-radius: 3px;
          object-fit: cover;
          box-shadow: var(--img-border);
        }

        .prefix-text {
          font-family: var(--ff-mono);
          font-size: calc(var(--fs-r, 1) * var(--fs-caption));
          font-weight: 700;
          color: var(--txt-heading);
        }

        .flag-selector i {
          font-size: 14px;
          color: var(--muted);
          transition: transform var(--t-ui);
        }

        .phone-group.dropdown-open .flag-selector i {
          transform: rotate(180deg);
        }

        .phone-input {
          flex: 1;
          min-width: 0;
          padding: 10px 14px;
          font-family: var(--ff-main);
          font-size: calc(var(--fs-r, 1) * var(--fs-body-sm));
          font-weight: 500;
          color: var(--txt-heading);
          background: transparent;
          border: none;
          outline: none;
          letter-spacing: 0.5px;
        }

        .phone-input::placeholder {
          color: var(--muted);
          font-weight: 400;
        }

        .validate-btn {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          background: var(--black-1);
          color: var(--bg);
          display: flex;
          align-items: center;
          justify-content: center;
          flex-shrink: 0;
          transition: transform var(--ease-snappy), background var(--t-ui), color var(--t-ui);
          cursor: pointer;
        }

        .validate-btn i {
          font-size: 18px;
        }

        .validate-btn:not(.is-success):hover {
          transform: scale(1.05);
          background: var(--black-4);
        }

        .validate-btn.is-success {
          background: rgb(40, 180, 75);
          color: rgb(var(--rgb-theme));
          pointer-events: none;
          box-shadow: none;
          transform: scale(1);
        }

        .validate-btn.is-success i {
          font-size: 20px;
        }

        .country-dropdown {
          position: absolute;
          top: calc(100% + 8px);
          left: 0;
          width: 100%;
          background: rgba(var(--rgb-theme), 0.94);
          backdrop-filter: blur(24px) saturate(180%);
          -webkit-backdrop-filter: blur(24px) saturate(180%);
          box-shadow: var(--ins-shadow);
          border-radius: var(--r);
          padding: 10px;
          opacity: 0;
          visibility: hidden;
          transform: translateY(-10px) scale(0.98);
          transition: opacity var(--t-ui), transform var(--t-ui), visibility 0s var(--t-ui);
          z-index: var(--z-pop);
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .phone-group.dropdown-open .country-dropdown {
          opacity: 1;
          visibility: visible;
          transform: translateY(0) scale(1);
          transition: opacity var(--t-ui), transform var(--t-ui);
        }

        .search-wrap {
          position: relative;
        }

        .search-wrap i {
          position: absolute;
          left: 12px;
          top: 50%;
          transform: translateY(-50%);
          color: var(--muted);
          font-size: 14px;
        }

        .search-input {
          width: 100%;
          padding: 10px 12px 10px 34px;
          border-radius: var(--r-xs);
          background: var(--surface);
          box-shadow: var(--ins-shadow);
          color: var(--txt-heading);
          font-size: calc(var(--fs-r, 1) * var(--fs-caption));
          font-family: var(--ff-main);
        }

        .search-input:focus {
          background: var(--card);
          box-shadow: var(--ins-shadow), inset 0 0 0 1px rgba(var(--rgb-primary), 0.15);
        }

        .country-list {
          max-height: 220px;
          overflow-y: auto;
          display: flex;
          flex-direction: column;
          gap: 2px;
          scrollbar-width: none;
          -ms-overflow-style: none;
        }

        .country-list::-webkit-scrollbar {
          display: none;
        }

        .country-item {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 10px 12px;
          border-radius: var(--r-xs);
          cursor: pointer;
          transition: background var(--t-ui);
        }

        .country-item:hover {
          background: var(--surface-hover);
        }

        .country-item.is-selected {
          background: rgba(var(--rgb-primary), 0.08);
        }

        .country-name {
          flex: 1;
          font-size: calc(var(--fs-r, 1) * var(--fs-body-sm));
          color: var(--txt-heading);
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .country-prefix {
          font-family: var(--ff-mono);
          font-size: calc(var(--fs-r, 1) * 11px);
          color: var(--muted);
        }

        .lightbox-overlay {
          position: fixed;
          inset: 0;
          z-index: var(--z-modal);
          background: rgba(var(--rgb-diff), 0.03);
          backdrop-filter: blur(14px);
          -webkit-backdrop-filter: blur(14px);
          display: flex;
          align-items: center;
          justify-content: center;
          padding: var(--s-4);
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.4s var(--ease), visibility 0.4s;
        }

        .lightbox-overlay.is-active {
          opacity: 1;
          visibility: visible;
        }

        .lightbox-modal {
          background: var(--bg);
          border-radius: var(--r-lg);
          padding: 40px 30px;
          width: 100%;
          max-width: 420px;
          box-shadow: var(--ins-shadow);
          transform: translateY(20px) scale(0.96);
          transition: transform 0.4s var(--ease-snappy);
          display: flex;
          flex-direction: column;
          align-items: center;
          text-align: center;
          gap: var(--s-4);
        }

        .lightbox-overlay.is-active .lightbox-modal {
          transform: translateY(0) scale(1);
        }

        .lightbox-icon {
          width: 64px;
          height: 64px;
          border-radius: 50%;
          background: var(--surface);
          box-shadow: var(--ins-shadow);
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 28px;
          margin-bottom: -10px;
        }

        .lightbox-title {
          font-family: var(--ff-heading);
          font-size: calc(var(--fs-r, 1) * var(--fs-h4));
          color: var(--txt-heading);
          font-weight: 700;
          letter-spacing: -0.02em;
          margin-bottom: 20px;
        }

        .lightbox-desc {
          font-size: calc(var(--fs-r, 1) * 16px);
          color: var(--txt-color);
          line-height: 1.6;
        }

        .interactive-area {
          position: relative;
          width: 100%;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
        }

        .verification-core {
          width: 100%;
          display: flex;
          flex-direction: column;
          gap: 20px;
          transition: filter 0.4s var(--ease), opacity 0.4s var(--ease);
        }

        .verification-core.is-muted {
          filter: blur(5px);
          opacity: 0.35;
          pointer-events: none;
          user-select: none;
        }

        .telegram-prestep {
          position: absolute;
          inset: 0;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          z-index: 10;
          background: transparent;
          transition: opacity 0.4s var(--ease), visibility 0.4s, transform 0.4s var(--ease-snappy);
        }

        .telegram-prestep.is-hidden {
          opacity: 0;
          visibility: hidden;
          transform: scale(0.96);
          pointer-events: none;
        }

        .btn-telegram {
          background: #24A1DE;
          color: #FFFFFF;
          width: auto;
          padding: 12px 28px;
          border: none;
          border-radius: var(--r-pill);
          font-family: var(--ff-main);
          font-size: calc(var(--fs-r, 1) * var(--fs-body-sm));
          font-weight: 600;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          box-shadow: 0 4px 12px rgba(36, 161, 222, 0.3);
          transition: background var(--t-ui), transform var(--ease-snappy), box-shadow var(--t-ui);
        }

        .btn-telegram:hover {
          background: #1C8CBF;
          transform: translateY(-2px);
          box-shadow: 0 6px 16px rgba(36, 161, 222, 0.4);
        }

        .code-inputs-container {
          display: flex;
          gap: 8px;
          justify-content: center;
          width: 100%;
        }

        .code-digit {
          width: 48px;
          height: 56px;
          border-radius: var(--r-xs);
          background: var(--white-5);
          box-shadow: var(--ins-shadow);
          font-family: var(--ff-mono);
          font-size: 24px;
          font-weight: 700;
          color: var(--txt-heading);
          text-align: center;
          transition: background var(--t-ui), box-shadow var(--t-ui), transform var(--ease-snappy);
        }

        .code-digit:focus {
          background: var(--white-8);
          box-shadow: var(--ins-shadow), inset 0 0 0 1.5px var(--txt-heading);
          transform: translateY(-2px);
          outline: none;
        }

        .code-digit.is-error {
          box-shadow: var(--ins-shadow), inset 0 0 0 1.5px var(--alert-red);
          animation: shake 0.4s var(--ease-snappy);
        }

        @keyframes shake {

          0%,
          100% {
            transform: translateX(0);
          }

          20%,
          60% {
            transform: translateX(-4px);
          }

          40%,
          80% {
            transform: translateX(4px);
          }
        }

        .lightbox-footer {
          width: 100%;
          display: flex;
          flex-direction: column;
          gap: 12px;
          margin-top: 8px;
        }

        .action-btn {
          width: 100%;
          padding: 14px 20px;
          border-radius: var(--r-pill);
          font-family: var(--ff-main);
          font-size: calc(var(--fs-r, 1) * var(--fs-body-sm));
          font-weight: 600;
          text-align: center;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          border: none;
          transition: background var(--t-ui), transform var(--ease-snappy), color var(--t-ui), box-shadow var(--t-ui);
        }

        .btn-verify {
          background: var(--black-1);
          color: var(--bg);
        }

        .btn-verify:not(.is-success):hover {
          background: var(--black-4);
          transform: translateY(-2px);
        }

        .btn-verify:disabled {
          opacity: 0.5;
          pointer-events: none;
        }

        .btn-verify.is-success {
          background: rgb(52, 199, 89) !important;
          color: rgb(var(--rgb-theme)) !important;
          pointer-events: none;
          box-shadow: var(--ins-shadow) !important;
        }

        .btn-verify.is-success i {
          font-size: 22px;
        }

        .resend-text {
          font-size: calc(var(--fs-r, 1) * 8.5px);
          color: var(--muted);
          font-family: var(--ff-mono);
          text-transform: uppercase;
          letter-spacing: 0.08em;
        }

        .resend-link {
          color: var(--txt-heading);
          cursor: pointer;
          text-decoration: underline;
          text-decoration-color: var(--white-10);
          text-underline-offset: 4px;
          transition: color var(--t-ui);
        }

        .resend-link:hover {
          color: var(--accent);
          text-decoration-color: var(--accent);
        }

        .hidden-input {
          position: absolute;
          opacity: 0;
          pointer-events: none;
        }

        .close-modal {
          position: absolute;
          top: 20px;
          right: 20px;
          width: 32px;
          height: 32px;
          border: none;
          border-radius: 50%;
          background: var(--surface);
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--muted);
          cursor: pointer;
          transition: background var(--t-ui), color var(--t-ui);
        }

        .close-modal:hover {
          background: var(--surface-hover);
          color: var(--txt-heading);
        }
      </style>
</head>

<body>

  <!-- Main Content Area -->
  <div class="main-container">

    <div class="header-text">
      <h1 class="display-text" style="font-size: calc(var(--fs-r, 1) * var(--fs-h3)); margin-bottom: 8px;">Secure Access</h1>
      <p class="txt-secondary">Enter your phone number to proceed.</p>
    </div>

    <!-- Phone Input Component -->
    <div class="phone-group" id="phoneGroup">

      <!-- Flag/Prefix Dropdown Trigger -->
      <div class="flag-selector" id="flagTrigger">
        <img src="https://flagpedia.net/data/flags/icon/36x27/br.png" alt="BR" class="flag-icon" id="selectedFlag">
        <span class="prefix-text" id="selectedPrefix">+55</span>
        <i class="ri-arrow-down-s-line"></i>
      </div>

      <!-- Phone Number Input -->
      <input type="tel" class="phone-input" id="phoneInput" placeholder="(21) 9 9999-9999" autocomplete="off">

      <!-- Validate Button -->
      <button class="validate-btn" id="validateBtn" aria-label="Validate phone number">
        <i class="ri-arrow-right-line" id="validateIcon"></i>
      </button>

      <!-- Dropdown Menu -->
      <div class="country-dropdown">
        <div class="search-wrap">
          <i class="ri-search-line"></i>
          <input type="text" class="search-input" id="countrySearch" placeholder="Search country...">
        </div>
        <div class="country-list" id="countryList">
          <!-- Populated via JS -->
        </div>
      </div>

    </div>
  </div>

  <!-- Lightbox Verification Modal -->
  <div class="lightbox-overlay" id="verificationModal">
    <div class="lightbox-modal">

      <button class="close-modal" id="closeModalBtn">
        <i class="ri-close-line"></i>
      </button>

      <div class="lightbox-icon">
        <i class="ri-telegram-fill" style="color: #24A1DE;"></i>
      </div>

      <div>
        <h3 class="lightbox-title">É você mesmo?</h3>
        <p class="lightbox-desc" style="margin-bottom: 12px;">Acreditamos em conexões reais e em uma comunidade protegida contra perfis falsos, golpes e acessos automatizados.</p>
        <p class="lightbox-desc">Para validar seu número, clique abaixo, solicite seu código ao nosso bot oficial no Telegram e retorne aqui para inserir o código recebido.</p>
      </div>

      <!-- Hidden input to capture pastes -->
      <input type="text" class="hidden-input" id="hiddenCodeInput" maxlength="6" autocomplete="one-time-code">

      <!-- Interactive Area: Pre-step overlay & Muted Inputs -->
      <div class="interactive-area">

        <!-- Centered Telegram Request Overlay -->
        <div class="telegram-prestep" id="telegramPreStep">
          <button class="btn-telegram" id="getTelegramCodeBtn">
            Solicitar código
          </button>
        </div>

        <!-- Muted Inputs Behind -->
        <div class="verification-core is-muted" id="verificationCore">
          <!-- 6-Digit Code Inputs -->
          <div class="code-inputs-container" id="codeContainer">
            <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="0">
            <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="1">
            <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="2">
            <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="3">
            <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="4">
            <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="5">
          </div>

          <div class="lightbox-footer">
            <button class="action-btn btn-verify" id="submitCodeBtn" disabled>Sou eu mesmo!</button>
            <span class="resend-text">Não recebeu o código? <a class="resend-link" id="resendLink">Solicite novamente</a></span>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script<?php echo $apollo_telegram_nonce_attr; ?>>
    // Apollo Phone Verification — mandatory design flow wired to REST
    // Phone -> request-support-verification -> bot delivers code -> verify-support-code

    document.addEventListener('DOMContentLoaded', () => {
    const phoneGroup = document.getElementById('phoneGroup');
    const flagTrigger = document.getElementById('flagTrigger');
    const selectedFlag = document.getElementById('selectedFlag');
    const selectedPrefix = document.getElementById('selectedPrefix');
    const phoneInput = document.getElementById('phoneInput');
    const validateBtn = document.getElementById('validateBtn');
    const validateIcon = document.getElementById('validateIcon');
    const countrySearch = document.getElementById('countrySearch');
    const countryList = document.getElementById('countryList');

    const verificationModal = document.getElementById('verificationModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const telegramPreStep = document.getElementById('telegramPreStep');
    const verificationCore = document.getElementById('verificationCore');
    const getTelegramCodeBtn = document.getElementById('getTelegramCodeBtn');
    const digits = Array.from(document.querySelectorAll('.code-digit'));
    const hiddenInput = document.getElementById('hiddenCodeInput');
    const submitCodeBtn = document.getElementById('submitCodeBtn');
    const resendLink = document.getElementById('resendLink');

    /* ══ 1. COUNTRY DROPDOWN ══ */
    const countries = [{
    name: "Brazil",
    prefix: "55",
    flag: "br"
    },
    {
    name: "United States",
    prefix: "1",
    flag: "us"
    },
    {
    name: "United Kingdom",
    prefix: "44",
    flag: "gb"
    },
    {
    name: "Portugal",
    prefix: "351",
    flag: "pt"
    },
    {
    name: "Germany",
    prefix: "49",
    flag: "de"
    },
    {
    name: "France",
    prefix: "33",
    flag: "fr"
    },
    {
    name: "Spain",
    prefix: "34",
    flag: "es"
    },
    {
    name: "Italy",
    prefix: "39",
    flag: "it"
    },
    {
    name: "Argentina",
    prefix: "54",
    flag: "ar"
    },
    {
    name: "Mexico",
    prefix: "52",
    flag: "mx"
    },
    {
    name: "Canada",
    prefix: "1",
    flag: "ca"
    },
    {
    name: "Australia",
    prefix: "61",
    flag: "au"
    },
    {
    name: "Japan",
    prefix: "81",
    flag: "jp"
    },
    {
    name: "India",
    prefix: "91",
    flag: "in"
    },
    {
    name: "Netherlands",
    prefix: "31",
    flag: "nl"
    }
    ];

    const CONFIG = window.apolloTelegramConfig || {
    restUrl: '/wp-json/apollo-telegram/v1',
    nonce: '',
    botUser: 'apolloRio_bot'
    };

    let currentPhone = null;
    let currentRequestId = null;
    let currentDeepLink = null;
    let pollTimer = null;

    function restPost(path, body) {
    return fetch(CONFIG.restUrl + path, {
    method: 'POST',
    headers: {
    'Content-Type': 'application/json',
    'X-Apollo-Telegram-Nonce': CONFIG.nonce
    },
    credentials: 'same-origin',
    body: JSON.stringify(Object.assign({
    nonce: CONFIG.nonce
    }, body || {}))
    });
    }

    function renderCountries(filter = '') {
    countryList.innerHTML = '';
    const lowerFilter = filter.toLowerCase();
    countries.forEach(country => {
    if (!country.name.toLowerCase().includes(lowerFilter) && !country.prefix.includes(lowerFilter)) return;
    const item = document.createElement('div');
    item.className = 'country-item';
    if (country.flag === selectedFlag.src.slice(-6, -4)) {
    item.classList.add('is-selected');
    }
    item.innerHTML = `
    <img src="https://flagpedia.net/data/flags/icon/36x27/${country.flag}.png" class="flag-icon" alt="${country.flag}">
    <span class="country-name">${country.name}</span>
    <span class="country-prefix">+${country.prefix}</span>
    `;
    item.addEventListener('click', () => {
    selectedFlag.src = `https://flagpedia.net/data/flags/icon/36x27/${country.flag}.png`;
    selectedPrefix.textContent = `+${country.prefix}`;
    phoneGroup.classList.remove('dropdown-open');
    countrySearch.value = "";
    renderCountries();
    setTimeout(() => {
    phoneInput.focus();
    phoneInput.dispatchEvent(new Event('input'));
    }, 100);
    });
    countryList.appendChild(item);
    });
    }

    renderCountries();

    flagTrigger.addEventListener('click', (e) => {
    e.stopPropagation();
    phoneGroup.classList.toggle('dropdown-open');
    if (phoneGroup.classList.contains('dropdown-open')) countrySearch.focus();
    });

    countrySearch.addEventListener('input', (e) => renderCountries(e.target.value));

    document.addEventListener('click', (e) => {
    if (!phoneGroup.contains(e.target)) phoneGroup.classList.remove('dropdown-open');
    });

    /* ══ 2. VALIDATION (SMART FORMATTING + ENTER) ══ */
    phoneInput.addEventListener('input', function() {
    if (selectedPrefix.textContent === '+55') {
    let val = this.value.replace(/\D/g, '').substring(0, 11);
    let formatted = '';
    if (val.length > 0) formatted = '(' + val.substring(0, 2);
    if (val.length > 2) formatted += ') ' + val.substring(2, 3);
    if (val.length > 3) formatted += ' ' + val.substring(3, 7);
    if (val.length > 7) formatted += '-' + val.substring(7, 11);
    this.value = formatted;
    } else {
    this.value = this.value.replace(/[^0-9\s()-]/g, '');
    }
    });

    phoneInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
    e.preventDefault();
    validateBtn.click();
    }
    });

    // Phone -> create pending verification -> open modal (pre-step muted state)
    validateBtn.addEventListener('click', async () => {
    const prefix = selectedPrefix.textContent.replace('+', '');
    const digitsOnly = phoneInput.value.replace(/\D/g, '');
    if (digitsOnly.length < 8) {
      phoneInput.style.transform="translateX(4px)" ;
      setTimeout(()=> phoneInput.style.transform = "translateX(-4px)", 100);
      setTimeout(() => phoneInput.style.transform = "translateX(0)", 200);
      return;
      }

      currentPhone = `+${prefix}${digitsOnly}`;

      // Pre-modal state
      validateBtn.style.pointerEvents = 'none';
      validateIcon.className = 'ri-loader-4-line ri-spin';
      phoneInput.disabled = true;
      flagTrigger.style.pointerEvents = 'none';

      try {
      const resp = await restPost('/request-support-verification', {
      phone: currentPhone
      });
      const data = await resp.json();

      if (data.success) {
      currentRequestId = data.request_id || null;
      currentDeepLink = data.deep_link || ('https://t.me/' + (CONFIG.botUser || 'apolloRio_bot'));
      setTimeout(() => openModal(), 300);
      } else {
      alert(data.message || (resp.status === 429 ?
      'Muitas tentativas. Aguarde alguns minutos.' :
      'Não foi possível iniciar a verificação. Tente novamente.'));
      resetPhoneRow();
      }
      } catch (err) {
      alert('Erro de rede. Tente novamente.');
      resetPhoneRow();
      }
      });

      function resetPhoneRow() {
      validateBtn.classList.remove('is-success');
      validateBtn.style.pointerEvents = 'auto';
      validateIcon.className = 'ri-arrow-right-line';
      phoneInput.disabled = false;
      flagTrigger.style.pointerEvents = 'auto';
      }

      /* ══ 3. LIGHTBOX / VERIFICATION CODE ══ */

      // Click to open Telegram (deep link carries the request_id) and un-mute inputs
      getTelegramCodeBtn.addEventListener('click', () => {
      window.open(currentDeepLink || ('https://t.me/' + (CONFIG.botUser || 'apolloRio_bot')), '_blank');

      telegramPreStep.classList.add('is-hidden');
      verificationCore.classList.remove('is-muted');

      startPolling();
      setTimeout(() => digits[0].focus(), 300);
      });

      // Local dev has no webhook: tick the plugin so the bot processes updates.
      // In production this endpoint is a no-op (mode: webhook) and polling stops.
      function startPolling() {
      stopPolling();
      pollTimer = setInterval(async () => {
      if (!currentRequestId) return stopPolling();
      try {
      const tick = await restPost('/poll-tick', {});
      const tickData = await tick.json().catch(() => ({}));
      if (tickData && tickData.mode === 'webhook') return stopPolling();

      const st = await restPost('/verification-status', {
      phone: currentPhone,
      request_id: currentRequestId
      });
      const stData = await st.json().catch(() => ({}));
      if (stData && stData.status === 'verified') stopPolling();
      } catch (e) {
      /* keep trying quietly */ }
      }, 5000);
      }

      function stopPolling() {
      if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
      }
      }

      function openModal() {
      verificationModal.classList.add('is-active');

      // Reset to pre-step muted state upon opening modal
      telegramPreStep.classList.remove('is-hidden');
      verificationCore.classList.add('is-muted');

      digits.forEach(d => {
      d.value = '';
      d.classList.remove('is-error');
      });
      hiddenInput.value = '';

      submitCodeBtn.classList.remove('is-success');
      submitCodeBtn.textContent = "Sou eu mesmo!";
      submitCodeBtn.disabled = true;
      submitCodeBtn.style.pointerEvents = 'auto';
      }

      function closeModal(resetPhone = true) {
      verificationModal.classList.remove('is-active');
      stopPolling();
      if (resetPhone) {
      resetPhoneRow();
      phoneInput.focus();
      } else {
      // Final verified state - lock as green with checkmark
      validateBtn.classList.add('is-success');
      validateBtn.style.pointerEvents = 'none';
      validateIcon.className = 'ri-check-line';
      }
      }

      closeModalBtn.addEventListener('click', () => closeModal(true));

      digits.forEach((digit, index) => {
      digit.addEventListener('input', () => {
      digit.classList.remove('is-error');
      digit.value = digit.value.replace(/[^0-9]/g, '');
      if (digit.value && index < digits.length - 1) {
        digits[index + 1].focus();
        }
        checkCodeCompletion();
        });
        digit.addEventListener('keydown', (e)=> {
        if (e.key === 'Backspace' && !digit.value && index > 0) {
        digits[index - 1].focus();
        digits[index - 1].value = '';
        }
        });
        digit.addEventListener('focus', () => digit.select());
        });

        verificationModal.addEventListener('paste', (e) => {
        const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        if (pasteData) {
        e.preventDefault();
        for (let i = 0; i < digits.length; i++) {
          digits[i].value=pasteData[i] || '' ;
          }
          const lastIndex=Math.min(pasteData.length - 1, 5);
          if (lastIndex>= 0) digits[lastIndex].focus();
          checkCodeCompletion();
          }
          });

          function checkCodeCompletion() {
          const code = digits.map(d => d.value).join('');
          submitCodeBtn.disabled = (code.length !== 6);
          }

          // Verify Submission — real check against the bot-delivered code
          submitCodeBtn.addEventListener('click', async () => {
          const code = digits.map(d => d.value).join('');

          if (!currentPhone || !currentRequestId || code.length !== 6) {
          digits.forEach(d => d.classList.add('is-error'));
          return;
          }

          submitCodeBtn.innerHTML = '<i class="ri-loader-4-line ri-spin" style="font-size: 20px;"></i>';
          submitCodeBtn.style.pointerEvents = 'none';

          try {
          const resp = await restPost('/verify-support-code', {
          phone: currentPhone,
          request_id: currentRequestId,
          code: code
          });
          const data = await resp.json();

          if (data.success) {
          submitCodeBtn.classList.add('is-success');
          submitCodeBtn.innerHTML = '<i class="ri-check-line"></i>';
          stopPolling();
          setTimeout(() => {
          closeModal(false); // lock phone row as green
          }, 1000);
          } else {
          digits.forEach(d => d.classList.add('is-error'));
          submitCodeBtn.textContent = (resp.status === 429) ? 'Limite atingido' : 'Código Inválido';
          setTimeout(() => {
          submitCodeBtn.textContent = "Sou eu mesmo!";
          submitCodeBtn.style.pointerEvents = 'auto';
          }, 2000);
          }
          } catch (err) {
          digits.forEach(d => d.classList.add('is-error'));
          submitCodeBtn.textContent = 'Erro de rede';
          setTimeout(() => {
          submitCodeBtn.textContent = "Sou eu mesmo!";
          submitCodeBtn.style.pointerEvents = 'auto';
          }, 2000);
          }
          });

          // Resend: new request_id for the same phone, back to pre-step
          resendLink.addEventListener('click', async () => {
          if (!currentPhone) return;
          resendLink.style.pointerEvents = 'none';
          try {
          const resp = await restPost('/request-support-verification', {
          phone: currentPhone
          });
          const data = await resp.json();
          if (data.success) {
          currentRequestId = data.request_id || currentRequestId;
          currentDeepLink = data.deep_link || currentDeepLink;
          telegramPreStep.classList.remove('is-hidden');
          verificationCore.classList.add('is-muted');
          digits.forEach(d => {
          d.value = '';
          d.classList.remove('is-error');
          });
          checkCodeCompletion();
          } else {
          alert(data.message || 'Não foi possível reenviar. Aguarde e tente novamente.');
          }
          } catch (err) {
          alert('Erro de rede. Tente novamente.');
          } finally {
          resendLink.style.pointerEvents = 'auto';
          }
          });
          });
          </script>
</body>

</html>