<?php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/portfolio_categories.php';

panel_require_login();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $allowed = (array)($config['portfolio']['allowed_categories'] ?? []);
    $defaultFromConfig = (string)($config['portfolio']['default_category'] ?? '');
    $categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../../portfolio/categories.json'));
    if (!in_array($defaultFromConfig, $allowed, true) && $allowed !== []) {
        $defaultFromConfig = (string)($allowed[0] ?? '');
    }

    $categories = portfolio_categories_load($categoriesPath, $defaultFromConfig, $allowed);

    $files = panel_list_portfolio_images($config);
    foreach ($files as &$f) {
        $name = (string)($f['name'] ?? '');
        $f['category'] = (string)(($categories['map'][$name] ?? '') ?: $categories['default']);
    }
    unset($f);

    panel_json([
        'ok' => true,
        'files' => $files,
        'allowedCategories' => array_values($allowed),
        'defaultCategory' => (string)($categories['default'] ?? $defaultFromConfig),
    ]);
}

if ($method !== 'POST') {
    panel_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

panel_require_csrf();

$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

if ($action === 'delete') {
    $name = isset($_POST['name']) && is_string($_POST['name']) ? $_POST['name'] : '';
    $name = panel_safe_basename($name);

    if ($name === '' || $name === 'categories.json') {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowa nazwa'], 400);
    }

    $ext = pathinfo($name, PATHINFO_EXTENSION);
    if (!panel_is_allowed_image_ext($ext)) {
        panel_json(['ok' => false, 'error' => 'Dozwolone są tylko pliki graficzne'], 400);
    }

    $path = panel_resolve_portfolio_path($config, $name);
    if (!is_file($path)) {
        panel_json(['ok' => false, 'error' => 'Plik nie istnieje'], 404);
    }

    if (!@unlink($path)) {
        panel_json(['ok' => false, 'error' => 'Nie udało się usunąć'], 500);
    }

    // Usuń ewentualne mapowanie kategorii i przelicz heurystyki
    $allowed = (array)($config['portfolio']['allowed_categories'] ?? []);
    $defaultFromConfig = (string)($config['portfolio']['default_category'] ?? '');
    $categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../../portfolio/categories.json'));
    if (!in_array($defaultFromConfig, $allowed, true) && $allowed !== []) {
        $defaultFromConfig = (string)($allowed[0] ?? '');
    }
    $categories = portfolio_categories_load($categoriesPath, $defaultFromConfig, $allowed);
    unset($categories['map'][$name]);
    portfolio_categories_write($categoriesPath, $categories);
    portfolio_update_categories($config);

    panel_json(['ok' => true]);
}

if ($action === 'rename') {
    $old = isset($_POST['old']) && is_string($_POST['old']) ? $_POST['old'] : '';
    $newBase = isset($_POST['new_base']) && is_string($_POST['new_base']) ? $_POST['new_base'] : '';

    $old = panel_safe_basename($old);
    if ($old === '' || $old === 'categories.json') {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowa nazwa'], 400);
    }

    $ext = pathinfo($old, PATHINFO_EXTENSION);
    if (!panel_is_allowed_image_ext($ext)) {
        panel_json(['ok' => false, 'error' => 'Dozwolone są tylko pliki graficzne'], 400);
    }

    $oldPath = panel_resolve_portfolio_path($config, $old);
    if (!is_file($oldPath)) {
        panel_json(['ok' => false, 'error' => 'Plik nie istnieje'], 404);
    }

    $newBase = panel_sanitize_filename_base($newBase);
    $newName = $newBase . '.' . strtolower($ext);

    if ($newName === 'categories.json') {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowa nazwa'], 400);
    }

    $newPath = panel_resolve_portfolio_path($config, $newName);
    if (file_exists($newPath)) {
        panel_json(['ok' => false, 'error' => 'Taki plik już istnieje'], 409);
    }

    if (!@rename($oldPath, $newPath)) {
        panel_json(['ok' => false, 'error' => 'Nie udało się zmienić nazwy'], 500);
    }

    // Przenieś mapowanie kategorii (jeśli istniało) + przelicz heurystyki
    $allowed = (array)($config['portfolio']['allowed_categories'] ?? []);
    $defaultFromConfig = (string)($config['portfolio']['default_category'] ?? '');
    $categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../../portfolio/categories.json'));
    if (!in_array($defaultFromConfig, $allowed, true) && $allowed !== []) {
        $defaultFromConfig = (string)($allowed[0] ?? '');
    }
    $categories = portfolio_categories_load($categoriesPath, $defaultFromConfig, $allowed);
    if (isset($categories['map'][$old]) && is_string($categories['map'][$old])) {
        $categories['map'][$newName] = $categories['map'][$old];
        unset($categories['map'][$old]);
        portfolio_categories_write($categoriesPath, $categories);
    }
    portfolio_update_categories($config);

    panel_json(['ok' => true, 'name' => $newName]);
}

if ($action === 'set_category') {
    $name = isset($_POST['name']) && is_string($_POST['name']) ? $_POST['name'] : '';
    $category = isset($_POST['category']) && is_string($_POST['category']) ? $_POST['category'] : '';

    $name = panel_safe_basename($name);
    if ($name === '' || $name === 'categories.json') {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowa nazwa'], 400);
    }
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    if (!panel_is_allowed_image_ext($ext)) {
        panel_json(['ok' => false, 'error' => 'Dozwolone są tylko pliki graficzne'], 400);
    }

    $path = panel_resolve_portfolio_path($config, $name);
    if (!is_file($path)) {
        panel_json(['ok' => false, 'error' => 'Plik nie istnieje'], 404);
    }

    $allowed = (array)($config['portfolio']['allowed_categories'] ?? []);
    $defaultFromConfig = (string)($config['portfolio']['default_category'] ?? '');
    $categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../../portfolio/categories.json'));
    if (!in_array($defaultFromConfig, $allowed, true) && $allowed !== []) {
        $defaultFromConfig = (string)($allowed[0] ?? '');
    }

    if (!in_array($category, $allowed, true)) {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowa kategoria'], 400);
    }

    $categories = portfolio_categories_load($categoriesPath, $defaultFromConfig, $allowed);
    if ($category === (string)($categories['default'] ?? $defaultFromConfig)) {
        unset($categories['map'][$name]);
    } else {
        $categories['map'][$name] = $category;
    }

    portfolio_categories_write($categoriesPath, $categories);
    panel_json(['ok' => true]);
}

if ($action === 'upload') {
    $panelCfg = panel_config($config);
    $maxBytes = max(1, $panelCfg['max_upload_mb']) * 1024 * 1024;

    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        panel_json(['ok' => false, 'error' => 'Brak pliku'], 400);
    }

    $f = $_FILES['file'];

    if (!isset($f['error'], $f['tmp_name'], $f['name'], $f['size'])) {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowy upload'], 400);
    }

    $uploadErr = (int)$f['error'];
    if ($uploadErr !== UPLOAD_ERR_OK) {
        $iniUpload = (string)ini_get('upload_max_filesize');
        $iniPost = (string)ini_get('post_max_size');

        $msg = 'Błąd uploadu.';
        if ($uploadErr === UPLOAD_ERR_INI_SIZE) {
            $msg = 'Plik przekracza limit PHP upload_max_filesize=' . $iniUpload . ' (oraz post_max_size=' . $iniPost . ').';
        } elseif ($uploadErr === UPLOAD_ERR_FORM_SIZE) {
            $msg = 'Plik przekracza limit formularza.';
        } elseif ($uploadErr === UPLOAD_ERR_PARTIAL) {
            $msg = 'Plik został wysłany tylko częściowo.';
        } elseif ($uploadErr === UPLOAD_ERR_NO_FILE) {
            $msg = 'Nie wybrano pliku.';
        } elseif ($uploadErr === UPLOAD_ERR_NO_TMP_DIR) {
            $msg = 'Brak katalogu tymczasowego na serwerze (UPLOAD_ERR_NO_TMP_DIR).';
        } elseif ($uploadErr === UPLOAD_ERR_CANT_WRITE) {
            $msg = 'Nie można zapisać pliku na dysk (UPLOAD_ERR_CANT_WRITE).';
        } elseif ($uploadErr === UPLOAD_ERR_EXTENSION) {
            $msg = 'Upload zatrzymany przez rozszerzenie PHP (UPLOAD_ERR_EXTENSION).';
        }

        panel_json(['ok' => false, 'error' => $msg], 400);
    }

    $tmp = (string)$f['tmp_name'];
    $origName = panel_safe_basename((string)$f['name']);
    $size = (int)$f['size'];

    if ($size <= 0 || $size > $maxBytes) {
        panel_json(['ok' => false, 'error' => 'Plik za duży'], 413);
    }

    $ext = strtolower((string)pathinfo($origName, PATHINFO_EXTENSION));
    if (!panel_is_allowed_image_ext($ext)) {
        panel_json(['ok' => false, 'error' => 'Dozwolone: jpg, jpeg, png, webp'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMime, true)) {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowy typ pliku'], 400);
    }

    $base = (string)pathinfo($origName, PATHINFO_FILENAME);
    $base = panel_sanitize_filename_base($base);

    $dir = panel_portfolio_dir($config);
    if (!is_dir($dir)) {
        panel_json(['ok' => false, 'error' => 'Brak katalogu portfolio'], 500);
    }

    $candidate = $base . '.' . $ext;
    $target = $dir . '/' . $candidate;

    $i = 1;
    while (file_exists($target)) {
        $candidate = $base . '-' . $i . '.' . $ext;
        $target = $dir . '/' . $candidate;
        $i++;
        if ($i > 500) {
            panel_json(['ok' => false, 'error' => 'Za dużo plików o tej nazwie'], 409);
        }
    }

    if (!@move_uploaded_file($tmp, $target)) {
        panel_json(['ok' => false, 'error' => 'Nie udało się zapisać pliku'], 500);
    }

    // Po uploadzie aktualizujemy categories.json (heurystyki + reguły)
    portfolio_update_categories($config);

    panel_json(['ok' => true, 'name' => $candidate]);
}

panel_json(['ok' => false, 'error' => 'Unknown action'], 400);
