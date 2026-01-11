# EcoShot — strona one‑page + portfolio + formularz kontaktowy

Prosty serwis w PHP (SPA router) dla strony ecoshot.com.pl — landing + portfolio zdjęć + formularz kontaktowy wysyłany przez SMTP (PHPMailer).

## Wymagania

- PHP >= 8.0
- Composer

## Instalacja

```bash
composer install
```

## Uruchomienie lokalnie

W repo jest router dla wbudowanego serwera PHP, który:
- serwuje pliki statyczne (np. `/assets`, `/portfolio`),
- pozostałe ścieżki (np. `/oferta`, `/kontakt`) kieruje do `index.php`.

Start:

```bash
php -S 127.0.0.1:8000 router.php
```

Następnie otwórz: http://127.0.0.1:8000

## Konfiguracja

Główna konfiguracja jest w `config.php` (zwraca tablicę PHP).

### SMTP (formularz kontaktowy)

Formularz obsługuje endpoint `POST /contact.php` i korzysta z PHPMailer.

Konfigurację SMTP ustawiaj przez zmienne środowiskowe (np. w panelu hostingu):

- `SMTP_HOST`
- `SMTP_PORT` (np. `587`)
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_SECURE` (`tls` | `ssl` | `none`)
- `SMTP_FROM_EMAIL`
- `SMTP_FROM_NAME`
- `SMTP_TO_EMAIL`
- `SMTP_TO_NAME`

Uwaga: sekrety (hasła) nie powinny być trzymane na stałe w repo — w praktyce zawsze nadpisuj wartości z `config.php` zmiennymi środowiskowymi.

## Portfolio

Zdjęcia trzymane są w katalogu `portfolio/` (pliki `.jpg/.jpeg/.png/.webp`).

Kategorie są mapowane w `portfolio/categories.json`:

- `default` — domyślna kategoria dla zdjęć bez przypisania
- `map` — mapowanie `nazwa_pliku.jpg` → `Kategoria`

Dodatkowo:
- dozwolone kategorie, limit zdjęć itd. są w `config.php` (`portfolio.*`)
- na stronie pliki są sortowane malejąco po dacie modyfikacji (fallback: nazwa)

### Aktualizacja kategorii (heurystyki)

Jest skrypt, który skanuje `portfolio/` i aktualizuje `portfolio/categories.json`:

```bash
php scripts/update_portfolio_categories.php
```

Skrypt próbuje automatycznie przypisać kategorie m.in. na podstawie:
- serii plików (np. `PSX_YYYYMMDD_*`, `PXL_YYYYMMDD_*`),
- numeracji `DSC01234.*`,
- słów kluczowych w nazwie pliku (np. `biznes`, `kobiece`, `krajobraz`).

## Struktura

- `index.php` — główna strona (render + logika portfolio)
- `contact.php` — API formularza kontaktowego (JSON)
- `router.php` — router dla `php -S` (SPA)
- `assets/` — CSS/JS i pliki statyczne
- `portfolio/` — zdjęcia oraz `categories.json`
- `scripts/` — skrypty pomocnicze

## Licencja

Brak zdefiniowanej licencji w repo. Jeśli chcesz ją dodać, doprecyzuj warunki (np. prywatne użycie / komercyjne). 
