<?php
declare(strict_types=1);

/**
 * Skanuje /portfolio i aktualizuje portfolio/categories.json.
 *
 * Heurystyki:
 * - propagacja kategorii w obrębie tej samej „serii” plików (np. PSX_YYYYMMDD_*, PXL_YYYYMMDD_*)
 * - propagacja kategorii w obrębie numerowanych serii (DSC00001.jpg itp.) na podstawie istniejących mapowań
 * - mapowanie po słowach kluczowych w nazwach plików (jeśli występują)
 */

$config = require __DIR__ . '/../config.php';

$allowed = (array)($config['portfolio']['allowed_categories'] ?? ['Rodzina', 'Kobiece', 'Biznes', 'Okolicznosciowe', 'Samochody', 'Portret', 'Krajobraz', 'Artystyczne', 'Zwierzęta']);
$defaultCategory = (string)($config['portfolio']['default_category'] ?? 'Rodzina');
$categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../portfolio/categories.json'));
$glob = (string)($config['portfolio']['files_glob'] ?? (__DIR__ . '/../portfolio/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}'));

if (!in_array($defaultCategory, $allowed, true)) {
    $defaultCategory = 'Rodzina';
}

/** @return array{default:string,map:array<string,string>} */
function loadCategories(string $path, string $defaultCategory, array $allowed): array
{
    $data = ['default' => $defaultCategory, 'map' => []];
    if (!is_file($path)) {
        return $data;
    }

    $raw = file_get_contents($path);
    $decoded = json_decode($raw ?: '', true);
    if (!is_array($decoded)) {
        return $data;
    }

    if (isset($decoded['default']) && is_string($decoded['default']) && in_array($decoded['default'], $allowed, true)) {
        $data['default'] = $decoded['default'];
    }

    if (isset($decoded['map']) && is_array($decoded['map'])) {
        foreach ($decoded['map'] as $file => $cat) {
            if (!is_string($file) || !is_string($cat)) {
                continue;
            }
            if (!in_array($cat, $allowed, true)) {
                continue;
            }
            $data['map'][$file] = $cat;
        }
    }

    return $data;
}

function writeCategories(string $path, array $data): void
{
    ksort($data['map']);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Nie udało się zserializować JSON.');
    }
    file_put_contents($path, $json . "\n");
}

function fileBaseName(string $path): string
{
    return basename($path);
}

/** @return array{prefix:string,date?:string,num?:int}|null */
function parseFileSignature(string $fileName): ?array
{
    // PSX_YYYYMMDD_XXXXXX.jpg
    if (preg_match('/^(PSX)_(\d{8})_\d+/i', $fileName, $m)) {
        return ['prefix' => strtoupper($m[1]), 'date' => $m[2]];
    }

    // PXL_YYYYMMDD_....
    if (preg_match('/^(PXL)_(\d{8})_/i', $fileName, $m)) {
        return ['prefix' => strtoupper($m[1]), 'date' => $m[2]];
    }

    // YYYYMMDD_HHMMSS... (częste z telefonów)
    if (preg_match('/^(\d{8})_\d{6}/', $fileName, $m)) {
        return ['prefix' => 'DATE', 'date' => $m[1]];
    }

    // DSC01234.jpg / DSC_01234.jpg
    if (preg_match('/^(DSC)_?(\d{3,6})/i', $fileName, $m)) {
        return ['prefix' => strtoupper($m[1]), 'num' => (int)$m[2]];
    }

    return null;
}

function detectByKeywords(string $fileName, array $allowed, string $defaultCategory): string
{
    $n = mb_strtolower($fileName);

    $rules = [
        // UWAGA: kategorie powinny być spójne z config.php (w razie braku w $allowed reguła jest pomijana)
        'Biznes' => ['biznes', 'biznesowe', 'business', 'linkedin', 'wizerunk', 'wizer', 'headshot', 'cv', 'corporate', 'branding', 'personal_brand'],
        'Okolicznosciowe' => ['okolicznosciowe', 'sesje_okolicznosciowe', 'okolicznosciow', 'okolicznościow', 'komun', 'komunia', 'communion', 'chrzest', 'slub', 'ślub', 'wesele'],
        'Kobiece' => ['kobiec', 'sesje_kobiece', 'women', 'woman', 'female', 'boudoir'],
        'Samochody' => ['samochod', 'samochody', 'auto', 'car', 'cars', 'motoryz', 'moto'],
        'Portret' => ['portret', 'portrait'],
        'Krajobraz' => ['krajobraz', 'landscape'],
        'Artystyczne' => ['artystycz', 'art', 'fineart', 'creative'],
        'Zwierzęta' => ['zwier', 'zwierze', 'zwierzę', 'zwierzeta', 'pies', 'kot', 'pet', 'dog', 'cat'],
        'Rodzina' => ['rodzin', 'rodzinne', 'family', 'dzieci', 'kids', 'mama', 'tata', 'plener', 'dom'],
    ];

    foreach ($rules as $cat => $needles) {
        if (!in_array($cat, $allowed, true)) {
            continue;
        }
        foreach ($needles as $needle) {
            if (str_contains($n, $needle)) {
                return $cat;
            }
        }
    }

    return $defaultCategory;
}

$categories = loadCategories($categoriesPath, $defaultCategory, $allowed);

$files = glob($glob, GLOB_BRACE) ?: [];
$baseNames = array_map('fileBaseName', $files);

// Usuń mapowania do plików, których nie ma już w /portfolio
$existing = array_fill_keys($baseNames, true);
foreach (array_keys($categories['map']) as $bn) {
    if (!isset($existing[$bn])) {
        unset($categories['map'][$bn]);
    }
}

// Zbierz sygnatury plików
$signatures = [];
foreach ($baseNames as $bn) {
    $sig = parseFileSignature($bn);
    if ($sig !== null) {
        $signatures[$bn] = $sig;
    }
}

// Grupuj po (prefix,date)
$groupsByDate = [];
foreach ($signatures as $bn => $sig) {
    if (isset($sig['date']) && is_string($sig['date'])) {
        $key = $sig['prefix'] . ':' . $sig['date'];
        $groupsByDate[$key][] = $bn;
    }
}

// Grupuj DSC po prefix i zbieraj numery
$dscFiles = [];
foreach ($signatures as $bn => $sig) {
    if (($sig['prefix'] ?? '') === 'DSC' && isset($sig['num'])) {
        $dscFiles[$bn] = (int)$sig['num'];
    }
}

$updated = 0;

// 1) Propagacja w grupach daty: jeśli któryś plik w grupie ma kategorię, daj ją reszcie
foreach ($groupsByDate as $key => $group) {
    $cat = null;
    foreach ($group as $bn) {
        if (isset($categories['map'][$bn])) {
            $cat = $categories['map'][$bn];
            break;
        }
    }
    if ($cat === null) {
        continue;
    }

    foreach ($group as $bn) {
        if (!isset($categories['map'][$bn])) {
            $categories['map'][$bn] = $cat;
            $updated++;
        }
    }
}

// 2) Propagacja dla DSC: zakres numeryczny na podstawie istniejących mapowań w tej serii
//    (działa dobrze, gdy kilka plików z jednej sesji ma już przypisaną kategorię)
$dscAnchorsByCategory = [];
foreach ($categories['map'] as $bn => $cat) {
    if (!isset($dscFiles[$bn])) {
        continue;
    }
    $num = $dscFiles[$bn];
    $dscAnchorsByCategory[$cat][] = $num;
}

foreach ($dscAnchorsByCategory as $cat => $nums) {
    if (!in_array($cat, $allowed, true)) {
        continue;
    }
    sort($nums);
    $min = $nums[0];
    $max = $nums[count($nums) - 1];

    // Bufor, żeby złapać sąsiadujące ujęcia z tej samej sesji
    $buffer = 40;
    $min -= $buffer;
    $max += $buffer;

    foreach ($dscFiles as $bn => $num) {
        if ($num < $min || $num > $max) {
            continue;
        }
        if (!isset($categories['map'][$bn])) {
            $categories['map'][$bn] = $cat;
            $updated++;
        }
    }
}

// 3) Mapowanie po słowach kluczowych dla pozostałych
foreach ($baseNames as $bn) {
    if (isset($categories['map'][$bn])) {
        continue;
    }

    $cat = detectByKeywords($bn, $allowed, $categories['default']);
    if ($cat !== $categories['default']) {
        $categories['map'][$bn] = $cat;
        $updated++;
    }
}

writeCategories($categoriesPath, $categories);

fwrite(STDOUT, "Zaktualizowano: {$updated} wpisów. Plik: {$categoriesPath}\n");
