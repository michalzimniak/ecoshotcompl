<?php
declare(strict_types=1);

// Router dla wbudowanego serwera PHP (php -S ... router.php)
// - serwuje istniejące pliki statyczne (assets/, portfolio/)
// - pozostałe ścieżki (np. /oferta) kieruje do index.php (SPA)

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

if ($path !== '/') {
    $filePath = __DIR__ . $path;
    if (is_file($filePath)) {
        return false;
    }
}

require __DIR__ . '/index.php';
