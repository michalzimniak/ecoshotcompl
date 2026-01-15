<?php
declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../lib/portfolio_categories.php';

panel_require_login();

$allowed = (array)($config['portfolio']['allowed_categories'] ?? []);
$defaultFromConfig = (string)($config['portfolio']['default_category'] ?? '');
$categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../../portfolio_media/categories.json'));

if (!in_array($defaultFromConfig, $allowed, true) && $allowed !== []) {
    $defaultFromConfig = (string)($allowed[0] ?? '');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $data = portfolio_categories_load($categoriesPath, $defaultFromConfig, $allowed);
    panel_json([
        'ok' => true,
        'allowed' => array_values($allowed),
        'path' => $categoriesPath,
        'default' => $data['default'],
        'rules' => $data['rules'] ?? [],
        'map' => $data['map'] ?? [],
    ]);
}

if ($method !== 'POST') {
    panel_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

panel_require_csrf();

$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

if ($action === 'save') {
    $default = isset($_POST['default']) && is_string($_POST['default']) ? $_POST['default'] : $defaultFromConfig;
    if (!in_array($default, $allowed, true)) {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowa domyślna kategoria'], 400);
    }

    $rulesRaw = isset($_POST['rules_json']) && is_string($_POST['rules_json']) ? $_POST['rules_json'] : '';
    $rulesDecoded = json_decode($rulesRaw, true);
    if (!is_array($rulesDecoded)) {
        panel_json(['ok' => false, 'error' => 'Nieprawidłowy JSON reguł'], 400);
    }

    // wczytaj istniejące mapowania, żeby ich nie nadpisać
    $data = portfolio_categories_load($categoriesPath, $defaultFromConfig, $allowed);
    $data['default'] = $default;

    $cleanRules = [];
    foreach ($rulesDecoded as $cat => $needles) {
        if (!is_string($cat) || !is_array($needles)) {
            continue;
        }
        if (!in_array($cat, $allowed, true)) {
            continue;
        }
        $cleanNeedles = [];
        foreach ($needles as $needle) {
            if (!is_string($needle)) {
                continue;
            }
            $needle = trim($needle);
            if ($needle === '') {
                continue;
            }
            $cleanNeedles[] = $needle;
        }
        if ($cleanNeedles !== []) {
            $cleanRules[$cat] = array_values(array_unique($cleanNeedles));
        }
    }

    $data['rules'] = $cleanRules;

    portfolio_categories_write($categoriesPath, $data);
    panel_json(['ok' => true]);
}

if ($action === 'regenerate') {
    $updated = portfolio_update_categories($config);
    panel_json(['ok' => true, 'updated' => $updated]);
}

panel_json(['ok' => false, 'error' => 'Unknown action'], 400);
