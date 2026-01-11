<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

$siteName = (string)($config['site']['brand'] ?? 'EcoShot');
$allowedCategories = (array)($config['portfolio']['allowed_categories'] ?? ['Rodzina', 'Kobiece', 'Biznes', 'Okolicznosciowe', 'Samochody', 'Portret', 'Krajobraz', 'Artystyczne', 'Zwierzęta']);
$defaultCategory = (string)($config['portfolio']['default_category'] ?? 'Rodzina');

$categoriesConfigPath = (string)($config['portfolio']['categories_config_path'] ?? (__DIR__ . '/portfolio/categories.json'));
$categoryMap = [];

if (is_file($categoriesConfigPath)) {
    $raw = file_get_contents($categoriesConfigPath);
    $decoded = json_decode($raw ?: '', true);
    if (is_array($decoded)) {
        if (isset($decoded['default']) && is_string($decoded['default']) && in_array($decoded['default'], $allowedCategories, true)) {
            $defaultCategory = $decoded['default'];
        }
        if (isset($decoded['map']) && is_array($decoded['map'])) {
            foreach ($decoded['map'] as $fileName => $category) {
                if (!is_string($fileName) || !is_string($category)) {
                    continue;
                }
                if (!in_array($category, $allowedCategories, true)) {
                    continue;
                }
                $categoryMap[$fileName] = $category;
            }
        }
    }
}

$portfolioGlob = (string)($config['portfolio']['files_glob'] ?? (__DIR__ . '/portfolio/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}'));
$portfolioUrlPrefix = (string)($config['portfolio']['url_prefix'] ?? '/portfolio/');
$portfolioFiles = glob($portfolioGlob, GLOB_BRACE) ?: [];

// Sort newest first if possible (fallback: name)
usort($portfolioFiles, static function (string $a, string $b): int {
    $ta = @filemtime($a) ?: 0;
    $tb = @filemtime($b) ?: 0;
    if ($ta === $tb) {
        return strcmp($b, $a);
    }
    return $tb <=> $ta;
});

$maxImages = (int)($config['portfolio']['max_images'] ?? 72);
$portfolioFiles = array_slice($portfolioFiles, 0, $maxImages);

function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function categoryFor(string $fileBaseName, array $categoryMap, string $defaultCategory): string {
    return $categoryMap[$fileBaseName] ?? $defaultCategory;
}

?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= esc((string)($config['site']['title'] ?? 'EcoShot Danuta Zimniak - Fotografia Bydgoszcz | Sesja rodzinna i biznesowa')) ?></title>
  <meta name="description" content="<?= esc((string)($config['site']['description'] ?? 'Naturalna fotografia rodzinna (lifestyle) oraz fotografia biznesowa i personal branding. Fotograf Bydgoszcz i okolice: plener, dom klienta, studio. Zdjęcia do LinkedIn Bydgoszcz.')) ?>" />

  <meta property="og:url" content="https://ecoshot.com.pl">
  <meta property="og:title" content="EcoShot Danuta Zimniak – Fotograf Bydgoszcz – sesja rodzinna i biznesowa | " />
  <meta property="og:description" content="Sesja rodzinna Bydgoszcz, sesja biznesowa Bydgoszcz, personal branding i zdjęcia do LinkedIn. Spokojny, naturalny styl." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="https://ecoshot.com.pl/assets/ecoshot_com_pl_preview.png" />
  
  <meta name="twitter:card" content="summary_large_image">
  <meta property="twitter:domain" content="ecoshot.com.pl">
  <meta property="twitter:url" content="https://ecoshot.com.pl">
  <meta name="twitter:title" content="EcoShot Danuta Zimniak – Fotograf Bydgoszcz – sesja rodzinna i biznesowa | ">
  <meta name="twitter:description" content="Sesja rodzinna Bydgoszcz, sesja biznesowa Bydgoszcz, personal branding i zdjęcia do LinkedIn. Spokojny, naturalny styl.">
  <meta name="twitter:image" content="https://ecoshot.com.pl/assets/ecoshot_com_pl_preview.png">
  
  <link rel="icon" href="<?= esc((string)($config['site']['assets']['logo'] ?? '/assets/logo.png')) ?>">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">

  <link href="/assets/css/styles.css" rel="stylesheet">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "EcoShot Danuta Zimniak – Fotograf Bydgoszcz",
    "areaServed": "Bydgoszcz i okolice",
    "description": "Naturalna fotografia rodzinna (lifestyle) oraz fotografia biznesowa i personal branding. Plener, dom klienta, studia w Bydgoszczy.",
    "image": "/assets/logo.png"
  }
  </script>
</head>
<body class="bg-soft">

<nav class="navbar navbar-expand-lg navbar-dark navbar-glass fixed-top" aria-label="Główna nawigacja">
  <div class="container">
    <a class="navbar-brand" href="/" data-route="/">
      <img src="<?= esc((string)($config['site']['assets']['logo'] ?? '/assets/logo.png')) ?>" alt="EcoShot – Fotograf Bydgoszcz" width="250" height="60" class="brand-logo" />
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Przełącz nawigację">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="/oferta" data-route="/oferta">Oferta</a></li>
        <li class="nav-item"><a class="nav-link" href="/cennik" data-route="/cennik">Cennik</a></li>
        <li class="nav-item"><a class="nav-link" href="/portfolio" data-route="/portfolio">Portfolio</a></li>
        <li class="nav-item"><a class="nav-link" href="/wspolpraca" data-route="/wspolpraca">Współpraca</a></li>
        <li class="nav-item"><a class="btn btn-sm btn-accent ms-lg-3" href="/kontakt" data-route="/kontakt"><i class="fa-regular fa-paper-plane me-2"></i>Kontakt</a></li>
      </ul>
    </div>
  </div>
</nav>

<header id="hero" class="hero">
  <video class="hero-video" autoplay muted loop playsinline preload="metadata" poster="<?= esc((string)($config['site']['assets']['hero_poster'] ?? '/assets/back1.png')) ?>">
    <source src="<?= esc((string)($config['site']['assets']['hero_video'] ?? '/assets/background.webm')) ?>" type="video/webm">
  </video>
  <div class="hero-overlay"></div>

  <div class="container hero-content">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <h1 class="display-5 fw-bold text-white animate__animated animate__fadeInUp">EcoShot Danuta Zimniak</h1>
        <h2 class="text-white animate__animated animate__fadeInUp">Naturalna fotografia rodzinna i biznesowa</h2>
        <p class="lead text-white-75 mt-3 animate__animated animate__fadeInUp animate__delay-1s">
          Sesja rodzinna Bydgoszcz (lifestyle), sesja biznesowa Bydgoszcz i personal branding.
          Plener, dom klienta lub wynajmowane studia w Bydgoszczy.
        </p>

        <div class="hero-badges mt-4 d-none d-md-flex animate__animated animate__fadeInUp animate__delay-2s">
          <div class="badge-item"><i class="fa-solid fa-leaf"></i><span>Naturalny styl</span></div>
          <div class="badge-item"><i class="fa-solid fa-location-dot"></i><span>Bydgoszcz i okolice</span></div>
          <div class="badge-item"><i class="fa-solid fa-camera"></i><span>Plener / dom / studio</span></div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card card-glass p-3 p-md-4 animate-on-scroll" data-animate="animate__fadeInRight">
          <div class="d-flex align-items-center gap-3">
            <img src="<?= esc((string)($config['site']['assets']['portrait'] ?? '/assets/danka.png')) ?>" alt="Fotograf – portret" class="avatar" />
            <div>
              <h2 class="h5 mb-1 text-white">Cześć! Jestem Danka</h2>
              <p class="mb-0 text-white-75">Fotografuję ludzi w ich naturalnym rytmie – spokojnie, bez presji, z prowadzeniem krok po kroku.</p>
            </div>
          </div>
          <hr class="hr-soft my-3">
          <ul class="list-unstyled mb-0 text-white-75 small">
            <li class="d-flex gap-2"><i class="fa-solid fa-check text-accent mt-1"></i><span>Minimalna stylizacja, maksimum emocji</span></li>
            <li class="d-flex gap-2"><i class="fa-solid fa-check text-accent mt-1"></i><span>Zdjęcia do LinkedIn Bydgoszcz i komunikacji marki</span></li>
            <li class="d-flex gap-2"><i class="fa-solid fa-check text-accent mt-1"></i><span>Szybka selekcja i elegancka obróbka</span></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</header>

<main>
  <section id="oferta" class="section section-bg" style="--section-bg: url('/assets/back6.png'); --section-bg-opacity: 0.15;">
    <div class="container">
      <div class="section-head">
        <h2 class="h1 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Oferta</h2>
        <p class="text-muted mb-0 animate-on-scroll" data-animate="animate__fadeInUp">
          Sesje rodzinne (lifestyle) oraz sesje biznesowe i personal branding – w Bydgoszczy i okolicach.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-12">
          <h3 id="oferta-rodzinne" class="h2 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Sesje rodzinne (lifestyle)</h3>
          <p class="text-muted mb-0 animate-on-scroll" data-animate="animate__fadeInUp">
            Naturalne kadry, ciepło i codzienność – w domu, w plenerze, na spacerze.
            Jeśli interesuje Cię sesja rodzinna Bydgoszcz, pomogę dobrać miejsce, porę dnia i klimat.
          </p>
        </div>

        <div class="col-12">
          <div class="row g-3 g-lg-4">
            <div class="col-md-6 col-lg-4">
              <div class="feature-card animate-on-scroll" data-animate="animate__fadeInUp">
                <div class="feature-icon"><i class="fa-solid fa-house"></i></div>
                <h4 class="h5">W domu klienta</h4>
                <p class="mb-0 text-muted">Najbardziej „Wasze” zdjęcia – bez stresu, w znanym otoczeniu.</p>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card animate-on-scroll" data-animate="animate__fadeInUp">
                <div class="feature-icon"><i class="fa-solid fa-tree"></i></div>
                <h4 class="h5">Plener w Bydgoszczy</h4>
                <p class="mb-0 text-muted">Park, las, łąka, miasto – dobiorę miejsce pod światło i porę roku.</p>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="feature-card animate-on-scroll" data-animate="animate__fadeInUp">
                <div class="feature-icon"><i class="fa-solid fa-baby"></i></div>
                <h4 class="h5">Noworodki i maluchy</h4>
                <p class="mb-0 text-muted">Delikatnie, bezpiecznie, z pauzami – tempo dopasowane do dziecka.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <hr class="my-4 my-lg-5">
        </div>

        <div class="col-12">
          <h3 id="oferta-biznesowe" class="h2 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Sesje biznesowe i personal branding</h3>
          <p class="text-muted mb-0 animate-on-scroll" data-animate="animate__fadeInUp">
            Wizerunek, który pracuje: zdjęcia do LinkedIn Bydgoszcz, www, prezentacji, ofert i social mediów.
            Sesja biznesowa Bydgoszcz może odbyć się w biurze, w plenerze lub w wynajmowanym studio.
          </p>
        </div>

        <div class="col-12">
          <div class="row g-3 g-lg-4 align-items-stretch">
            <div class="col-lg-6">
              <div class="callout animate-on-scroll" data-animate="animate__fadeInLeft">
                <h4 class="h4">Dla kogo?</h4>
                <ul class="mb-0">
                  <li>specjaliści i freelancerzy</li>
                  <li>właściciele firm</li>
                  <li>zespoły i kadra zarządzająca</li>
                  <li>eksperci budujący markę osobistą</li>
                </ul>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="callout animate-on-scroll" data-animate="animate__fadeInRight">
                <h4 class="h4">Co dostajesz?</h4>
                <ul class="mb-0">
                  <li>prowadzenie pozowania i mini scenariusz</li>
                  <li>spójny zestaw kadrów do komunikacji</li>
                  <li>wersje pod web + social</li>
                  <li>retusz w nowoczesnym, naturalnym stylu</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="cennik" class="section section-bg" style="--section-bg: url('/assets/back5.png'); --section-bg-opacity: 0.15;">
    <div class="container">
      <div class="section-head">
        <h2 class="h1 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Cennik</h2>
        <p class="text-muted mb-0 animate-on-scroll" data-animate="animate__fadeInUp">
          Proste pakiety i jasne zasady. Dokładną propozycję dopasuję do Waszych potrzeb, lokalizacji i czasu.
        </p>
      </div>

      <div class="row g-3 g-lg-4">
        <div class="col-12">
          <h3 class="h3 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Sesje rodzinne</h3>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="price-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="d-flex justify-content-between align-items-start">
              <h3 class="h5 mb-1">Pakiet Basic</h3>
              <span class="tag">800 zł</span>
            </div>
            <p class="text-muted small mb-3">Sesja rodzinna (lifestyle)</p>
            <div class="price">800 zł</div>
            <ul class="small text-muted">
              <li>45–60 minut</li>
              <li>15 zdjęć po obróbce</li>
              <li>galeria online</li>
            </ul>
            <a class="btn btn-outline-dark w-100" href="/kontakt" data-route="/kontakt"
               data-prefill-service="Sesja rodzinna (lifestyle)"
               data-prefill-message="Dzień dobry! Interesuje mnie Pakiet Basic – 800 zł (45–60 min, 15 zdjęć, galeria online). Proszę o dostępne terminy w Bydgoszczy/okolicach.">
              Zapytaj o termin
            </a>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="price-card featured animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="d-flex justify-content-between align-items-start">
              <h3 class="h5 mb-1">Pakiet Standard</h3>
              <span class="tag">950–1000 zł</span>
            </div>
            <p class="text-muted small mb-3">Sesja rodzinna (lifestyle)</p>
            <div class="price">950–1000 zł</div>
            <ul class="small text-muted">
              <li>60–90 minut</li>
              <li>20 zdjęć po obróbce</li>
              <li>pomoc w doborze ubrań</li>
            </ul>
            <a class="btn btn-accent w-100" href="/kontakt" data-route="/kontakt"
               data-prefill-service="Sesja rodzinna (lifestyle)"
               data-prefill-message="Dzień dobry! Interesuje mnie Pakiet Standard – 950–1000 zł (60–90 min, 20 zdjęć, pomoc w doborze ubrań). Proszę o dostępne terminy.">
              Umów sesję
            </a>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="price-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="d-flex justify-content-between align-items-start">
              <h3 class="h5 mb-1">Pakiet Premium</h3>
              <span class="tag">1200–1400 zł</span>
            </div>
            <p class="text-muted small mb-3">Sesja rodzinna (lifestyle)</p>
            <div class="price">1200–1400 zł</div>
            <ul class="small text-muted">
              <li>do 2 godzin</li>
              <li>30 zdjęć po obróbce</li>
              <li>album lub odbitki</li>
            </ul>
            <a class="btn btn-outline-dark w-100" href="/kontakt" data-route="/kontakt"
               data-prefill-service="Sesja rodzinna (lifestyle)"
               data-prefill-message="Dzień dobry! Interesuje mnie Pakiet Premium – 1200–1400 zł (do 2h, 30 zdjęć, album lub odbitki). Proszę o terminy i szczegóły.">
              Zapytaj o szczegóły
            </a>
          </div>
        </div>

        <div class="col-md-6 col-lg-3">
          <div class="price-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="d-flex justify-content-between align-items-start">
              <h3 class="h5 mb-1">Mini sesje</h3>
              <span class="tag">450–600 zł</span>
            </div>
            <p class="text-muted small mb-3">Krótkie sesje tematyczne</p>
            <div class="price">450–600 zł</div>
            <ul class="small text-muted">
              <li>limitowane terminy</li>
              <li>idealne na prezent</li>
              <li>plener / studio</li>
            </ul>
            <a class="btn btn-outline-dark w-100" href="/kontakt" data-route="/kontakt"
               data-prefill-service="Sesja rodzinna (lifestyle)"
               data-prefill-message="Dzień dobry! Interesują mnie mini sesje sezonowe – 450–600 zł. Jakie są aktualne terminy i motyw przewodni?">
              Sprawdź mini sesje
            </a>
          </div>
        </div>

        <div class="col-12 mt-2">
          <h3 class="h3 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Sesje biznesowe</h3>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="price-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="d-flex justify-content-between align-items-start">
              <h3 class="h5 mb-1">Sesja LinkedIn / wizerunkowa</h3>
              <span class="tag">800–900 zł</span>
            </div>
            <p class="text-muted small mb-3">Idealna na profil i ofertę</p>
            <div class="price">800–900 zł</div>
            <ul class="small text-muted">
              <li>60 minut</li>
              <li>10 zdjęć po obróbce</li>
              <li>prawa komercyjne</li>
            </ul>
            <a class="btn btn-outline-dark w-100" href="/kontakt" data-route="/kontakt"
               data-prefill-service="Zdjęcia do LinkedIn"
               data-prefill-message="Dzień dobry! Interesuje mnie sesja LinkedIn / wizerunkowa – 800–900 zł (60 min, 10 zdjęć, prawa komercyjne). Proszę o dostępne terminy w Bydgoszczy.">
              Zapytaj o termin
            </a>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="price-card featured animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="d-flex justify-content-between align-items-start">
              <h3 class="h5 mb-1">Personal Branding</h3>
              <span class="tag">1500–1600 zł</span>
            </div>
            <p class="text-muted small mb-3">Spójna sesja pod markę osobistą</p>
            <div class="price">1500–1600 zł</div>
            <ul class="small text-muted">
              <li>koncepcja sesji</li>
              <li>2 stylizacje</li>
              <li>20 zdjęć po obróbce</li>
            </ul>
            <a class="btn btn-accent w-100" href="/kontakt" data-route="/kontakt"
               data-prefill-service="Sesja biznesowa / personal branding"
               data-prefill-message="Dzień dobry! Interesuje mnie Personal Branding – 1500–1600 zł (koncepcja, 2 stylizacje, 20 zdjęć). Proszę o krótką wycenę i propozycje terminów.">
              Umów konsultację
            </a>
          </div>
        </div>
      </div>

      <div class="note mt-4 animate-on-scroll" data-animate="animate__fadeInUp">
        <div class="d-flex gap-3 align-items-start">
          <i class="fa-solid fa-circle-info text-accent mt-1"></i>
          <p class="mb-0 text-muted">
            Studio w razie potrzeby jest wynajmowane i wliczone w cenę.
            Dojazd na terenie Bydgoszczy w cenie, okolice ustalane indywidualnie. Dodatkowe zdjęcia możliwe do dokupienia.
          </p>
        </div>
      </div>
    </div>
  </section>

  <section id="portfolio" class="section section-alt section-bg">
    <div class="container">
      <div class="section-head">
        <h2 class="h1 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Portfolio</h2>
        <p class="text-muted mb-0 animate-on-scroll" data-animate="animate__fadeInUp">
          Poniżej znajdziesz wybrane zdjęcia. Filtruj wg kategorii: <?= esc(implode(', ', array_values($allowedCategories))) ?>.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-filter active" data-filter="all">Wszystkie</button>
        <?php foreach (array_values($allowedCategories) as $cat):
            if (!is_string($cat) || $cat === '') {
                continue;
            }
        ?>
          <button type="button" class="btn btn-sm btn-filter" data-filter="<?= esc($cat) ?>"><?= esc($cat) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="row g-2 g-md-3" id="portfolioGrid">
        <?php if (count($portfolioFiles) === 0): ?>
          <div class="col-12">
            <div class="alert alert-warning mb-0">Brak zdjęć w folderze <strong>/portfolio/</strong>.</div>
          </div>
        <?php else: ?>
          <?php foreach ($portfolioFiles as $filePath):
              $base = basename($filePath);
              $category = categoryFor($base, $categoryMap, $defaultCategory);
                $url = $portfolioUrlPrefix . rawurlencode($base);
              $title = $category . ' – ' . preg_replace('/\.[^.]+$/', '', $base);
          ?>
            <div class="col-6 col-md-4 col-lg-3 portfolio-item" data-category="<?= esc($category) ?>">
              <a href="<?= esc($url) ?>" class="portfolio-link glightbox" data-gallery="portfolio" data-title="<?= esc($title) ?>">
                <img src="<?= esc($url) ?>" class="img-fluid portfolio-img" alt="<?= esc($category) ?> – zdjęcie" loading="lazy">
                <span class="portfolio-badge"><?= esc($category) ?></span>
              </a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section id="wspolpraca" class="section section-bg" style="--section-bg: url('/assets/back3.png'); --section-bg-opacity: 0.15;">
    <div class="container">
      <div class="section-head">
        <h2 class="h1 mb-2 animate-on-scroll" data-animate="animate__fadeInUp">Jak wygląda współpraca</h2>
        <p class="text-muted mb-0 animate-on-scroll" data-animate="animate__fadeInUp">
          Prosty proces, dużo spokoju i jasne ustalenia – tak, aby sesja była przyjemnością.
        </p>
      </div>

      <div class="row g-3 g-lg-4">
        <div class="col-md-6 col-lg-3">
          <div class="step-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="step-no">1</div>
            <h3 class="h6">Kontakt i potrzeby</h3>
            <p class="text-muted mb-0">Krótko rozmawiamy o celu (rodzina / biznes), stylu i miejscu w Bydgoszczy.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="step-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="step-no">2</div>
            <h3 class="h6">Plan i przygotowanie</h3>
            <p class="text-muted mb-0">Podpowiadam stylizacje, tło, światło i „co zrobić z rękami”.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="step-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="step-no">3</div>
            <h3 class="h6">Sesja</h3>
            <p class="text-muted mb-0">Prowadzę krok po kroku, dbam o komfort i naturalne emocje.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="step-card animate-on-scroll" data-animate="animate__fadeInUp">
            <div class="step-no">4</div>
            <h3 class="h6">Selekcja i oddanie</h3>
            <p class="text-muted mb-0">Otrzymujesz galerię online, a gotowe pliki w wersjach web i druk.</p>
          </div>
        </div>
      </div>

      <div class="cta-band mt-4 animate-on-scroll" data-animate="animate__fadeInUp">
        <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
          <div>
            <h3 class="h4 mb-1">Chcesz sprawdzić dostępne terminy?</h3>
            <p class="mb-0 text-muted">Napisz – odpowiem szybko i konkretnie.</p>
          </div>
          <a class="btn btn-accent btn-lg" href="/kontakt" data-route="/kontakt"><i class="fa-regular fa-envelope me-2"></i>Napisz wiadomość</a>
        </div>
      </div>
    </div>
  </section>

  <section id="kontakt" class="section section-contact" style="--contact-bg: url('<?= esc((string)($config['site']['assets']['contact_bg'] ?? '/assets/back_contact.png')) ?>');">
    <div class="container">
      <div class="section-head">
        <h2 class="h1 mb-2 text-white animate-on-scroll" data-animate="animate__fadeInUp">Kontakt</h2>
        <p class="text-white-75 mb-0 animate-on-scroll" data-animate="animate__fadeInUp">
          Napisz kilka słów – wrócę z propozycją terminu i dopasowaną ofertą (Bydgoszcz i okolice).
        </p>
      </div>

      <div class="row g-3 g-lg-4">
        <div class="col-lg-5">
          <div class="card card-glass p-3 p-md-4 animate-on-scroll" data-animate="animate__fadeInLeft">
            <h3 class="h5 text-white">Szybkie dane</h3>
            <div class="contact-line"><i class="fa-solid fa-location-dot"></i><span>Bydgoszcz i okolice</span></div>
            <div class="contact-line"><i class="fa-solid fa-camera"></i><span>Plener • dom klienta • studio</span></div>
            <div class="contact-line"><i class="fa-regular fa-clock"></i><span>Terminy: pn–sb</span></div>
            <hr class="hr-soft">
            <p class="mb-0 text-white-75 small">Uwaga: w formularzu nie podawaj danych wrażliwych. Zostaw kontakt, a wszystko ustalimy w rozmowie.</p>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card card-glass p-3 p-md-4 animate-on-scroll" data-animate="animate__fadeInRight">
            <form id="contactForm" novalidate>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-white" for="name">Imię i nazwisko</label>
                  <input class="form-control" id="name" name="name" required minlength="2" maxlength="80" placeholder="np. Anna Kowalska">
                  <div class="invalid-feedback">Podaj imię i nazwisko (min. 2 znaki).</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-white" for="email">E-mail</label>
                  <input type="email" class="form-control" id="email" name="email" required maxlength="120" placeholder="np. anna@firma.pl">
                  <div class="invalid-feedback">Podaj poprawny adres e-mail.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-white" for="phone">Telefon (opcjonalnie)</label>
                  <input class="form-control" id="phone" name="phone" maxlength="30" placeholder="np. 600 000 000">
                </div>
                <div class="col-md-6">
                  <label class="form-label text-white" for="service">Rodzaj sesji</label>
                  <select class="form-select" id="service" name="service" required>
                    <option value="" selected disabled>Wybierz…</option>
                    <option>Rodzinna (lifestyle)</option>
                    <option>Biznesowa / Personal branding</option>
                    <option>Samochód</option>
                    <option>Sesja kobieca</option>
                    <option>Inne</option>
                  </select>
                  <div class="invalid-feedback">Wybierz rodzaj sesji.</div>
                </div>

                <div class="col-12">
                  <label class="form-label text-white" for="message">Wiadomość</label>
                  <textarea class="form-control" id="message" name="message" rows="5" required minlength="10" maxlength="2000" placeholder="Napisz: termin, liczba osób, preferowane miejsce (plener/dom/studio), inspiracje…"></textarea>
                  <div class="invalid-feedback">Wiadomość jest zbyt krótka (min. 10 znaków).</div>
                </div>

                <div class="col-12">
                  <input type="text" class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="gdpr" name="gdpr" required>
                    <label class="form-check-label text-white-75" for="gdpr">
                      Wyrażam zgodę na kontakt w sprawie zapytania (RODO).
                    </label>
                    <div class="invalid-feedback">Zgoda jest wymagana, aby wysłać wiadomość.</div>
                  </div>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                  <button class="btn btn-accent btn-lg" type="submit" id="contactSubmit">
                    <span class="btn-label"><i class="fa-regular fa-paper-plane me-2"></i>Wyślij wiadomość</span>
                    <span class="btn-loading" aria-hidden="true"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Wysyłanie…</span>
                  </button>
                  <div id="contactAlert" class="flex-grow-1"></div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>

<footer class="footer">
  <div class="container">
    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
      <div class="text-muted small">© <?= esc((string)date('Y')) ?> EcoShot Danuta Zimniak – Fotografia Bydgoszcz</div>
      <?php
        $facebookUrl = (string)($config['site']['social']['facebook'] ?? '');
        $instagramUrl = (string)($config['site']['social']['instagram'] ?? '');
      ?>

      <?php if ($facebookUrl !== '' || $instagramUrl !== ''): ?>
        <div class="footer-social" aria-label="Social media">
          <?php if ($facebookUrl !== ''): ?>
            <a class="social-link" href="<?= esc($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
              <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
            </a>
          <?php endif; ?>

          <?php if ($instagramUrl !== ''): ?>
            <a class="social-link" href="<?= esc($instagramUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
              <i class="fa-brands fa-instagram" aria-hidden="true"></i>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js" defer></script>
<script src="/assets/js/main.js" defer></script>
</body>
</html>
