(function () {
  'use strict';

  const DISMISS_KEY = 'pwa_install_dismissed_v1';

  function isStandalone() {
    return window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
  }

  function isIos() {
    const ua = navigator.userAgent || '';
    return /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
  }

  function isLocalhostOrHttps() {
    const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    return location.protocol === 'https:' || isLocalhost;
  }

  function dismissed() {
    try {
      return localStorage.getItem(DISMISS_KEY) === '1';
    } catch (_) {
      return false;
    }
  }

  function setDismissed() {
    try {
      localStorage.setItem(DISMISS_KEY, '1');
    } catch (_) {
      // ignore
    }
  }

  function ensureBanner() {
    if (document.getElementById('pwa-install-banner')) return document.getElementById('pwa-install-banner');

    const wrap = document.createElement('div');
    wrap.id = 'pwa-install-banner';
    wrap.className = 'position-fixed start-0 end-0 bottom-0 p-2';
    wrap.style.zIndex = '1080';

    wrap.innerHTML = `
      <div class="container">
        <div class="alert alert-primary shadow-sm d-flex align-items-center gap-2 mb-0" role="alert">
          <img src="/panel/icons/icon.svg" alt="" width="28" height="28" style="flex:0 0 auto">
          <div class="small" style="min-width:0">
            <div class="fw-semibold">Zainstaluj Panel Portfolio</div>
            <div class="opacity-75" id="pwa-install-sub">Na telefonie działa jak aplikacja.</div>
          </div>
          <div class="ms-auto d-flex align-items-center gap-2">
            <button type="button" class="btn btn-light btn-sm" id="pwa-install-btn">Zainstaluj</button>
            <button type="button" class="btn btn-link btn-sm text-white-50 text-decoration-none" id="pwa-install-close" aria-label="Zamknij">✕</button>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(wrap);

    const closeBtn = wrap.querySelector('#pwa-install-close');
    closeBtn?.addEventListener('click', () => {
      setDismissed();
      wrap.remove();
    });

    return wrap;
  }

  function showIosHint() {
    const banner = ensureBanner();
    const btn = banner.querySelector('#pwa-install-btn');
    const sub = banner.querySelector('#pwa-install-sub');

    if (sub) {
      sub.textContent = 'iPhone/iPad: Udostępnij → Dodaj do ekranu.';
    }
    if (btn) {
      btn.textContent = 'Jak dodać';
      btn.addEventListener('click', async () => {
        if (window.Swal?.fire) {
          await window.Swal.fire({
            icon: 'info',
            title: 'Instalacja na iOS',
            html: 'Otwórz w Safari → przycisk „Udostępnij” → „Dodaj do ekranu początkowego”.',
            confirmButtonText: 'OK'
          });
        }
      }, { once: true });
    }
  }

  // Nie pokazuj, jeśli już zainstalowane albo środowisko nie wspiera
  if (isStandalone() || dismissed() || !isLocalhostOrHttps()) return;

  let deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    const banner = ensureBanner();
    const btn = banner.querySelector('#pwa-install-btn');

    btn?.addEventListener('click', async () => {
      if (!deferredPrompt) return;
      try {
        await deferredPrompt.prompt();
        await deferredPrompt.userChoice;
      } finally {
        deferredPrompt = null;
        banner.remove();
      }
    }, { once: true });
  });

  // iOS nie emituje beforeinstallprompt – pokaż hint
  if (isIos()) {
    showIosHint();
  }
})();
