<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';

$cookieParams = session_get_cookie_params();

session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'] ?? '/',
    'domain' => $cookieParams['domain'] ?? '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function panel_config(array $config): array
{
    $panel = $config['panel'] ?? [];
    if (!is_array($panel)) {
        $panel = [];
    }

    return [
        'username' => (string)($panel['username'] ?? 'admin'),
        'password' => array_key_exists('password', $panel) ? (string)$panel['password'] : '',
        'password_hash' => array_key_exists('password_hash', $panel) ? (string)$panel['password_hash'] : '',
        'max_upload_mb' => (int)($panel['max_upload_mb'] ?? 20),
    ];
}

function panel_ensure_csrf_token(): string
{
    if (empty($_SESSION['panel_csrf']) || !is_string($_SESSION['panel_csrf'])) {
        $_SESSION['panel_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['panel_csrf'];
}

function panel_require_csrf(): void
{
    $sent = '';
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'];
    } elseif (isset($_POST['csrf']) && is_string($_POST['csrf'])) {
        $sent = $_POST['csrf'];
    }

    $expected = $_SESSION['panel_csrf'] ?? '';
    if (!is_string($expected) || $expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'CSRF']);
        exit;
    }
}

function panel_is_logged_in(): bool
{
    return !empty($_SESSION['panel_logged_in']) && $_SESSION['panel_logged_in'] === true;
}

function panel_require_login(): void
{
    if (!panel_is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }
}

function panel_login(array $config, string $username, string $password): bool
{
    $panel = panel_config($config);

    if (!hash_equals($panel['username'], $username)) {
        return false;
    }

    $hash = trim($panel['password_hash']);
    if ($hash !== '') {
        return password_verify($password, $hash);
    }

    $plain = (string)$panel['password'];
    if ($plain === '') {
        return false;
    }

    return hash_equals($plain, $password);
}

function panel_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function panel_portfolio_dir(array $config): string
{
    $dir = __DIR__ . '/../portfolio';
    if (isset($config['portfolio']['dir']) && is_string($config['portfolio']['dir']) && $config['portfolio']['dir'] !== '') {
        $dir = $config['portfolio']['dir'];
    }
    return rtrim($dir, '/');
}

function panel_portfolio_url_prefix(array $config): string
{
    $prefix = (string)($config['portfolio']['url_prefix'] ?? '/portfolio/');
    if ($prefix === '') {
        $prefix = '/portfolio/';
    }
    if (substr($prefix, -1) !== '/') {
        $prefix .= '/';
    }
    return $prefix;
}

function panel_is_allowed_image_ext(string $ext): bool
{
    $ext = strtolower(ltrim($ext, '.'));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
}

function panel_sanitize_filename_base(string $base): string
{
    $base = trim($base);
    // Zamiast spacji stosujemy '_' (żeby unikać %20 w URL-ach itp.)
    $base = preg_replace('/\s+/u', '_', $base) ?? '';
    $base = preg_replace('/_+/u', '_', $base) ?? '';
    $base = preg_replace('/[^\p{L}\p{N} _.-]+/u', '', $base) ?? '';
    // po podmianie spacji na '_' nie chcemy końcówkowych separatorów
    $base = trim($base, " .-_\t\n\r\0\x0B");
    if ($base === '') {
        $base = 'plik';
    }
    return $base;
}

function panel_safe_basename(string $name): string
{
    $name = basename($name);
    $name = str_replace(["\0", "\r", "\n"], '', $name);
    return $name;
}

function panel_resolve_portfolio_path(array $config, string $fileName): string
{
    $fileName = panel_safe_basename($fileName);
    $dir = panel_portfolio_dir($config);
    return $dir . '/' . $fileName;
}

function panel_list_portfolio_images(array $config): array
{
    $dir = panel_portfolio_dir($config);
    $prefix = panel_portfolio_url_prefix($config);

    if (!is_dir($dir)) {
        return [];
    }

    $files = scandir($dir);
    if ($files === false) {
        return [];
    }

    $out = [];
    foreach ($files as $entry) {
        if (!is_string($entry) || $entry === '.' || $entry === '..') {
            continue;
        }
        if ($entry === 'categories.json') {
            continue;
        }

        $path = $dir . '/' . $entry;
        if (!is_file($path)) {
            continue;
        }

        $ext = pathinfo($entry, PATHINFO_EXTENSION);
        if (!panel_is_allowed_image_ext($ext)) {
            continue;
        }

        $out[] = [
            'name' => $entry,
            'url' => $prefix . rawurlencode($entry),
            'size' => @filesize($path) ?: 0,
            'mtime' => @filemtime($path) ?: 0,
        ];
    }

    usort($out, static function (array $a, array $b): int {
        $ta = (int)($a['mtime'] ?? 0);
        $tb = (int)($b['mtime'] ?? 0);
        if ($ta === $tb) {
            return strcmp((string)($b['name'] ?? ''), (string)($a['name'] ?? ''));
        }
        return $tb <=> $ta;
    });

    return $out;
}
