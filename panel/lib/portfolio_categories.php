<?php
declare(strict_types=1);

// Wspólne funkcje do zarządzania portfolio_media/categories.json.
// Używane przez panel oraz opcjonalnie przez skrypty CLI.

if (!function_exists('portfolio_str_contains')) {
    function portfolio_str_contains(string $haystack, string $needle): bool
    {
        if (function_exists('str_contains')) {
            return str_contains($haystack, $needle);
        }
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('portfolio_strtolower')) {
    function portfolio_strtolower(string $s): string
    {
        if (function_exists('mb_strtolower')) {
            return (string)mb_strtolower($s);
        }
        return strtolower($s);
    }
}

/**
 * Domyślne reguły przypisywania kategorii po nazwie pliku.
 * Panel może je nadpisać zapisując "rules" w portfolio_media/categories.json.
 *
 * @return array<string, array<int, string>>
 */
function portfolio_categories_default_rules(): array
{
    return [
        // UWAGA: kategorie powinny być spójne z config.php (w razie braku w $allowed reguła jest pomijana)
        'Biznes' => ['biznes', 'biznesowe', 'business', 'linkedin', 'wizerunk', 'wizer', 'headshot', 'cv', 'corporate', 'branding', 'personal_brand'],
        'Okolicznosciowe' => ['okolicznosciowe', 'sesje_okolicznosciowe', 'okolicznosciow', 'okolicznościow', 'komun', 'komunia', 'communion', 'chrzest', 'slub', 'ślub', 'wesele'],
        'Kobiece' => ['kobiec', 'sesje_kobiece', 'women', 'woman', 'female', 'boudoir'],
        'Samochody' => ['samochod', 'samochody', 'auto', 'car', 'cars', 'motoryz', 'moto'],
        'Sport' => ['sport', 'sports', 'mecz', 'pilk', 'siatkowk', 'koszyk', 'bieg', 'runner', 'run', 'fitness', 'trening', 'gym', 'rower', 'cycling', 'bike'],
        'Produktowe' => ['produkt', 'produktowe', 'product', 'produkty', 'packshot', 'pack-shot', 'ecommerce', 'e-commerce', 'sklep', 'shop', 'komercyjne', 'commercial', 'jedzenie', 'food', 'gastro', 'gastronomia', 'restaurac', 'restaurant', 'menu', 'kuchnia', 'kulinar', 'catering', 'potraw', 'danie', 'dessert', 'ciast', 'napoj', 'drink', 'coffee', 'kawa'],
        'Portret' => ['portret', 'portrait'],
        'Krajobraz' => ['krajobraz', 'landscape'],
        'Artystyczne' => ['artystycz', 'art', 'fineart', 'creative'],
        'Zwierzęta' => ['zwier', 'zwierze', 'zwierzę', 'zwierzeta', 'pies', 'kot', 'pet', 'dog', 'cat'],
        'Rodzina' => ['rodzin', 'rodzinne', 'family', 'dzieci', 'kids', 'mama', 'tata', 'plener', 'dom'],
    ];
}

/**
 * @param array<int,string> $allowed
 * @return array{default:string,map:array<string,string>,rules:array<string,array<int,string>>}
 */
function portfolio_categories_load(string $path, string $defaultCategory, array $allowed): array
{
    $data = ['default' => $defaultCategory, 'map' => [], 'rules' => portfolio_categories_default_rules()];
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

    if (isset($decoded['rules']) && is_array($decoded['rules'])) {
        $rules = [];
        foreach ($decoded['rules'] as $cat => $needles) {
            if (!is_string($cat) || !is_array($needles)) {
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
                $rules[$cat] = array_values(array_unique($cleanNeedles));
            }
        }

        if ($rules !== []) {
            $data['rules'] = $rules;
        }
    }

    return $data;
}

function portfolio_categories_write(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir)) {
        throw new RuntimeException('Brak katalogu na categories.json: ' . $dir);
    }

    ksort($data['map']);
    if (isset($data['rules']) && is_array($data['rules'])) {
        ksort($data['rules']);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Nie udało się zserializować JSON.');
    }
    $ok = @file_put_contents($path, $json . "\n");
    if ($ok === false) {
        throw new RuntimeException('Nie udało się zapisać pliku: ' . $path);
    }
}

function portfolio_file_basename(string $path): string
{
    return basename($path);
}

/** @return array{prefix:string,date?:string,num?:int}|null */
function portfolio_parse_file_signature(string $fileName): ?array
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

/**
 * @param array<int,string> $allowed
 * @param array<string,array<int,string>> $rules
 */
function portfolio_detect_by_keywords(string $fileName, array $allowed, string $defaultCategory, array $rules): string
{
    $n = portfolio_strtolower($fileName);

    foreach ($rules as $cat => $needles) {
        if (!in_array($cat, $allowed, true)) {
            continue;
        }
        foreach ($needles as $needle) {
            if (!is_string($needle) || $needle === '') {
                continue;
            }
            if (portfolio_str_contains($n, portfolio_strtolower($needle))) {
                return $cat;
            }
        }
    }

    return $defaultCategory;
}

/**
 * Główna funkcja: skanuje /portfolio_media i aktualizuje portfolio_media/categories.json.
 * Zwraca liczbę dopisanych/zmienionych wpisów.
 */
function portfolio_update_categories(array $config): int
{
    $allowed = (array)($config['portfolio']['allowed_categories'] ?? ['Rodzina']);
    $defaultCategory = (string)($config['portfolio']['default_category'] ?? 'Rodzina');
    $categoriesPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/../../portfolio_media/categories.json'));
    $glob = (string)($config['portfolio']['files_glob'] ?? (__DIR__ . '/../../portfolio_media/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}'));

    if (!in_array($defaultCategory, $allowed, true)) {
        $defaultCategory = (string)($allowed[0] ?? 'Rodzina');
    }

    $categories = portfolio_categories_load($categoriesPath, $defaultCategory, $allowed);

    $files = glob($glob, GLOB_BRACE) ?: [];
    $baseNames = array_map('portfolio_file_basename', $files);

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
        $sig = portfolio_parse_file_signature($bn);
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

    // 1) Propagacja w grupach daty
    foreach ($groupsByDate as $group) {
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

    // 2) Propagacja dla DSC
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

        $cat = portfolio_detect_by_keywords($bn, $allowed, (string)$categories['default'], (array)($categories['rules'] ?? []));
        if ($cat !== (string)$categories['default']) {
            $categories['map'][$bn] = $cat;
            $updated++;
        }
    }

    portfolio_categories_write($categoriesPath, $categories);
    return $updated;
}
