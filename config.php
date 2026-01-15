<?php
declare(strict_types=1);

/**
 * Centralna konfiguracja strony.
 *
 * Sekrety (hasła SMTP) ustawiaj przez zmienne środowiskowe (.env / panel hostingu),
 * a nie na stałe w repo.
 */

if (!function_exists('env')) {
    function env(string $key, string $default = ''): string
    {
        $v = getenv($key);
        return ($v === false) ? $default : (string)$v;
    }
}

return [
    'site' => [
        'brand' => 'EcoShot',
        'city' => 'Bydgoszcz',
        'title' => 'EcoShot Danuta Zimniak - Fotografia Bydgoszcz',
        'description' => 'Naturalna fotografia rodzinna (lifestyle) oraz fotografia biznesowa i personal branding. Fotograf Bydgoszcz i okolice: plener, dom klienta, studio. Zdjęcia do LinkedIn Bydgoszcz.',
        'social' => [
            'facebook' => 'https://www.facebook.com/ZimniakDanuta',
            'instagram' => 'https://www.instagram.com/portret_danuta_zimniak',
        ],
        'assets' => [
            'logo' => '/assets/logo.png',
            'hero_video' => '/assets/background.webm',
            'hero_poster' => '/assets/back1.png',
            'contact_bg' => '/assets/back_contact.png',
            'portrait' => '/assets/danka.png',
            'preview' => '/assets/ecoshot_com_pl_preview.png',
        ],
    ],

    'portfolio' => [
        'allowed_categories' => ['Rodzina', 'Kobiece', 'Biznes', 'Okolicznosciowe', 'Samochody', 'Sport', 'Produktowe', 'Portret', 'Krajobraz', 'Artystyczne', 'Zwierzęta'],
        'default_category' => 'Rodzina',
        'max_images' => 72,
        'categories_config_path' => __DIR__ . '/portfolio_media/categories.json',
        'files_glob' => __DIR__ . '/portfolio_media/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}',
        'url_prefix' => '/portfolio_media/',
    ],

    'mail' => [
        'smtp' => [
            'host' => env('SMTP_HOST', 'zimniak-tech.pl'),
            'port' => (int)env('SMTP_PORT', '587'),
            'user' => env('SMTP_USER', 'formularz@ecoshot.com.pl'),
            'pass' => env('SMTP_PASS', 'f89P3g9^i'),
            // tls|ssl|none
            'secure' => strtolower(env('SMTP_SECURE', 'tls')),

            'from_email' => env('SMTP_FROM_EMAIL', env('SMTP_USER', 'formularz@ecoshot.com.pl')),
            'from_name' => env('SMTP_FROM_NAME', 'EcoShot Webpage – Formularz'),
            'to_email' => env('SMTP_TO_EMAIL', 'danuta@ecoshot.com.pl'),
            'to_name' => env('SMTP_TO_NAME', 'Danuta Zimniak'),
        ],
    ],

    // Panel administracyjny: /panel/
    // Zalecane: ustaw 'password_hash' (hash z password_hash('TwojeHaslo', PASSWORD_DEFAULT))
    // Alternatywnie: możesz ustawić 'password' (plaintext) – działa, ale mniej bezpieczne.
    'panel' => [
        'username' => 'danka',
        'password_hash' => '',
        'password' => 'adoa2223abz',
        'max_upload_mb' => 20,
    ],
];
