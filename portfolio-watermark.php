<?php
declare(strict_types=1);

// Dynamiczny watermark (logo) dla zdjęć portfolio – używany tylko przez stronę główną.
// Zwraca obraz z "wypalonym" logo w prawym dolnym rogu.

$config = require __DIR__ . '/config.php';

$name = isset($_GET['f']) && is_string($_GET['f']) ? $_GET['f'] : '';
$name = basename($name);

if ($name === '' || $name === 'categories.json') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Bad request";
    exit;
}

$ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(415);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Unsupported file type";
    exit;
}

$glob = (string)($config['portfolio']['files_glob'] ?? (__DIR__ . '/portfolio_media/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}'));
$portfolioDir = dirname($glob);
if (!is_dir($portfolioDir)) {
    $portfolioDir = __DIR__ . '/portfolio_media';
}

$srcPath = rtrim($portfolioDir, '/'). '/' . $name;
if (!is_file($srcPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found";
    exit;
}

$logoUrl = (string)($config['site']['assets']['logo'] ?? '/assets/logo.png');
$logoPath = '';
if ($logoUrl !== '' && $logoUrl[0] === '/') {
    $candidate = __DIR__ . $logoUrl;
    if (is_file($candidate)) {
        $logoPath = $candidate;
    }
}
if ($logoPath === '') {
    $fallback = __DIR__ . '/assets/logo.png';
    if (is_file($fallback)) {
        $logoPath = $fallback;
    }
}

if ($logoPath === '' || !is_file($logoPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Logo not found";
    exit;
}

$srcMtime = @filemtime($srcPath) ?: 0;
$srcSize = @filesize($srcPath) ?: 0;
$logoMtime = @filemtime($logoPath) ?: 0;
$etag = 'W/"' . sha1($name . '|' . $srcMtime . '|' . $srcSize . '|' . $logoMtime) . '"';

if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string)$_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    header('ETag: ' . $etag);
    http_response_code(304);
    exit;
}

// Wymiary (bez GD) do obliczenia skali/paddingu
$info = @getimagesize($srcPath);
if (!is_array($info) || !isset($info[0], $info[1])) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Cannot read image size";
    exit;
}

$w = (int)$info[0];
$h = (int)$info[1];
if ($w <= 0 || $h <= 0) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Invalid image size";
    exit;
}

// Skala watermarku: ~22% szerokości zdjęcia, z sensownymi limitami.
$targetW = (int)round($w * 0.22);
$targetW = max(140, min(420, $targetW));

// Padding: ~3% krótszego boku
$pad = (int)max(12, round(min($w, $h) * 0.03));

// Walidacja realpath (ochrona przed kombinowaniem ścieżką)
$realDir = realpath($portfolioDir);
$realSrc = realpath($srcPath);
if ($realDir === false || $realSrc === false || strpos($realSrc, $realDir . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Bad request";
    exit;
}

$magick = trim((string)@shell_exec('command -v magick'));
if ($magick === '') {
    $magick = trim((string)@shell_exec('command -v convert'));
}
if ($magick === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ImageMagick not available";
    exit;
}

header('ETag: ' . $etag);
header('Cache-Control: public, max-age=86400');

$outputFormat = '';
if ($ext === 'jpg' || $ext === 'jpeg') {
    header('Content-Type: image/jpeg');
    $outputFormat = 'jpg:-';
} elseif ($ext === 'png') {
    header('Content-Type: image/png');
    $outputFormat = 'png:-';
} elseif ($ext === 'webp') {
    header('Content-Type: image/webp');
    $outputFormat = 'webp:-';
}

// Zbuduj komendę ImageMagick bez powłoki (argumenty jako lista).
// magick input ( logo -resize Wx ) -gravity southeast -geometry +pad+pad -composite -strip ... output
$cmd = [
    $magick,
    $realSrc,
    '(',
    $logoPath,
    '-resize',
    $targetW . 'x',
    ')',
    '-gravity',
    'southeast',
    '-geometry',
    '+' . $pad . '+' . $pad,
    '-composite',
    '-strip',
];

if ($ext === 'jpg' || $ext === 'jpeg') {
    $cmd[] = '-quality';
    $cmd[] = '92';
} elseif ($ext === 'png') {
    $cmd[] = '-define';
    $cmd[] = 'png:compression-level=6';
} elseif ($ext === 'webp') {
    $cmd[] = '-quality';
    $cmd[] = '85';
}

$cmd[] = $outputFormat;

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$proc = @proc_open($cmd, $descriptors, $pipes);
if (!is_resource($proc)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Cannot start ImageMagick";
    exit;
}

fclose($pipes[0]);

// Streamuj wynik do klienta
while (!feof($pipes[1])) {
    $chunk = fread($pipes[1], 8192);
    if ($chunk === false) {
        break;
    }
    echo $chunk;
}
fclose($pipes[1]);

$err = stream_get_contents($pipes[2]);
fclose($pipes[2]);

$code = proc_close($proc);
if ($code !== 0) {
    // Jeżeli ImageMagick poległ, nie zwracamy binarnego śmietnika.
    // Uwaga: jeśli coś już poszło do outputu, nie cofniemy.
    // W praktyce IM zwykle nie wypuszcza nic na stdout przy błędzie.
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    if ($err) {
        echo "\n" . $err;
    }
}
