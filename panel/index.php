<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$panelCfg = panel_config($config);
$csrf = panel_ensure_csrf_token();

$error = '';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    header('Location: /panel/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? (string)$_POST['password'] : '';

    if (panel_login($config, $username, $password)) {
        session_regenerate_id(true);
        $_SESSION['panel_logged_in'] = true;
        panel_ensure_csrf_token();
        header('Location: /panel/');
        exit;
    }

    $error = 'Niepoprawny login lub hasło.';
}

$loggedIn = panel_is_logged_in();

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?><!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel – Portfolio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="manifest" href="/panel/manifest.webmanifest">
  <meta name="theme-color" content="#0d6efd">
  <link rel="apple-touch-icon" href="/assets/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">
  <style>
    .thumb { aspect-ratio: 4/3; object-fit: cover; width: 100%; border-radius: .5rem; }
    .filename { word-break: break-all; }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <span class="navbar-brand d-flex align-items-center gap-2">
      <img src="/assets/logo.png" alt="Logo" width="250" height="60" style="object-fit:contain">
      <span>Panel Portfolio</span>
    </span>
    <div class="ms-auto">
      <?php if ($loggedIn): ?>
        <a class="btn btn-outline-secondary btn-sm me-2" href="/panel/categories.php">Kategorie</a>
        <a class="btn btn-outline-secondary btn-sm" href="/panel/?logout=1">Wyloguj</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">

<?php if (!$loggedIn): ?>
  <div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h5 mb-3">Logowanie</h1>

          <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= h($error) ?></div>
          <?php endif; ?>

          <form method="post" autocomplete="off" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
              <label class="form-label">Login</label>
              <input class="form-control" name="username" required minlength="2" maxlength="64" autocomplete="username">
              <div class="invalid-feedback">Wpisz login.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Hasło</label>
              <input class="form-control" type="password" name="password" required minlength="4" maxlength="200" autocomplete="current-password">
              <div class="invalid-feedback">Wpisz hasło.</div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Zaloguj</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach((form) => {
        form.addEventListener('submit', (event) => {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();
  </script>

<?php else: ?>

  <?php
    $iniUpload = (string)ini_get('upload_max_filesize');
    $iniPost = (string)ini_get('post_max_size');
    $cfgMax = (int)($panelCfg['max_upload_mb'] ?? 20);
  ?>

  <div class="row g-3 align-items-end mb-3">
    <div class="col-12 col-lg-8">
      <h1 class="h4 mb-1">Pliki w katalogu /portfolio</h1>
      <div class="text-muted">Tylko grafiki (.jpg/.jpeg/.png/.webp). Plik categories.json jest pomijany.</div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card">
        <div class="card-body">
          <label class="form-label">Upload (możesz wybrać wiele plików)</label>
          <input id="uploadInput" type="file" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
          <button id="uploadBtn" class="btn btn-primary w-100 mt-2" type="button">Wyślij</button>
          <div id="uploadStatus" class="small text-muted mt-2"></div>
          <div class="small text-muted mt-2">
            Limit panelu: <?= h((string)$cfgMax) ?> MB. Limity PHP: upload_max_filesize=<?= h($iniUpload) ?>, post_max_size=<?= h($iniPost) ?>.
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3">
    <button id="refreshBtn" class="btn btn-outline-secondary btn-sm" type="button">Odśwież</button>
    <input id="searchInput" class="form-control form-control-sm" style="max-width: 320px" placeholder="Szukaj po nazwie...">
    <div class="ms-auto small text-muted" id="countInfo"></div>
  </div>

  <div id="grid" class="row g-3"></div>

  <div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Zmień nazwę</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2 small text-muted">Rozszerzenie pozostanie bez zmian.</div>
          <div class="mb-3">
            <label class="form-label">Nowa nazwa (bez rozszerzenia)</label>
            <input id="renameInput" class="form-control">
          </div>
          <div id="renameError" class="alert alert-danger d-none" role="alert"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button id="renameSaveBtn" type="button" class="btn btn-primary">Zapisz</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    window.PANEL = {
      csrf: <?= json_encode($csrf, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
      apiUrl: '/panel/api/portfolio.php'
    };
  </script>
  <script>
    (function () {
      if (!('serviceWorker' in navigator)) return;
      // PWA działa tylko na HTTPS albo localhost
      const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
      if (location.protocol !== 'https:' && !isLocalhost) return;

      navigator.serviceWorker.register('/panel/sw.js').catch(() => {
        // bez krzyczenia w UI
      });
    })();
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
  <script src="/panel/pwa-install.js"></script>
  <script src="/panel/panel.js"></script>

<?php endif; ?>

</div>
</body>
</html>
