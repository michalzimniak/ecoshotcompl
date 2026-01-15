<?php
declare(strict_types=1);

/**
 * Skanuje /portfolio_media i aktualizuje portfolio_media/categories.json.
 *
 * Heurystyki:
 * - propagacja kategorii w obrębie tej samej „serii” plików (np. PSX_YYYYMMDD_*, PXL_YYYYMMDD_*)
 * - propagacja kategorii w obrębie numerowanych serii (DSC00001.jpg itp.) na podstawie istniejących mapowań
 * - mapowanie po słowach kluczowych w nazwach plików (jeśli występują)
 */

require_once __DIR__ . '/../panel/lib/portfolio_categories.php';

// Tryb CLI (zachowujemy dotychczasowe zachowanie skryptu)
if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath((string)$_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $config = require __DIR__ . '/../config.php';
    $updated = portfolio_update_categories($config);

    $categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../portfolio_media/categories.json'));
    fwrite(STDOUT, "Zaktualizowano: {$updated} wpisów. Plik: {$categoriesPath}\n");
}
