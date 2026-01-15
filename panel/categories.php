<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!panel_is_logged_in()) {
    header('Location: /panel/');
    exit;
}

$csrf = panel_ensure_csrf_token();

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?><!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel – Kategorie</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="manifest" href="/panel/manifest.webmanifest">
  <meta name="theme-color" content="#0d6efd">
  <link rel="apple-touch-icon" href="/assets/logo.png">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <span class="navbar-brand">Panel</span>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="/panel/">Pliki</a>
      <a class="btn btn-outline-secondary btn-sm" href="/panel/?logout=1">Wyloguj</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Kategorie i reguły</h1>
    <button id="reloadBtn" class="btn btn-outline-secondary btn-sm" type="button">Odśwież</button>
    <div class="ms-auto">
      <button id="regenerateBtn" class="btn btn-outline-primary btn-sm" type="button">Przelicz (auto)</button>
      <button id="saveBtn" class="btn btn-primary btn-sm" type="button">Zapisz reguły</button>
    </div>
  </div>

  <div class="alert alert-info small">
    Reguły są zapisywane w <code>portfolio/categories.json</code> w polu <code>rules</code>. “Przelicz” uruchamia heurystyki z <code>scripts/update_portfolio_categories.php</code>.
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card">
        <div class="card-body">
          <label class="form-label">Domyślna kategoria</label>
          <select id="defaultSelect" class="form-select"></select>
          <div class="form-text">Jeśli plik nie ma przypisania w <code>map</code>, używana jest ta kategoria.</div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <div class="small text-muted">Podpowiedź formatu reguł</div>
          <pre class="small mb-0">{
  "Biznes": ["biznes", "linkedin"],
  "Rodzina": ["rodzin", "kids"]
}</pre>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-body">
          <label class="form-label">Reguły (JSON)</label>
          <textarea id="rulesJson" class="form-control" rows="18" spellcheck="false"></textarea>
          <div class="form-text">Wartości to listy słów-kluczy (substring). Kategorie muszą istnieć w config.php.</div>
        </div>
      </div>

      <div class="small text-muted mt-2" id="status"></div>
    </div>
  </div>

</div>

<script>
  window.PANEL = {
    csrf: <?= json_encode($csrf, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    apiUrl: '/panel/api/categories.php'
  };
</script>
<script>
  (function () {
    if (!('serviceWorker' in navigator)) return;
    const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    if (location.protocol !== 'https:' && !isLocalhost) return;
    navigator.serviceWorker.register('/panel/sw.js').catch(() => {});
  })();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/panel/pwa-install.js"></script>
<script src="/panel/categories.js"></script>

</body>
</html>
