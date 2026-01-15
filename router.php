<?php
declare(strict_types=1);

// Router dla wbudowanego serwera PHP (php -S ... router.php)
// - serwuje istniejące pliki statyczne (assets/, portfolio/)
// - pozostałe ścieżki (np. /oferta) kieruje do index.php (SPA)

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

// Obsługa /panel/ na wbudowanym serwerze (gdy żądana jest ścieżka katalogu).
if ($path === '/panel' || $path === '/panel/') {
    $panelIndex = __DIR__ . '/panel/index.php';
    if (is_file($panelIndex)) {
        require $panelIndex;
        return true;
    }
}

if ($path !== '/') {
    $filePath = __DIR__ . $path;
    if (is_file($filePath)) {
        return false;
    }

    if (is_dir($filePath)) {
        $index = rtrim($filePath, '/') . '/index.php';
        if (is_file($index)) {
            require $index;
            return true;
        }
    }
}

require __DIR__ . '/index.php';
