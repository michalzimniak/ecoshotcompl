<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda niedozwolona.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Honeypot (anti-bot)
$honeypot = trim((string)($_POST['website'] ?? ''));
if ($honeypot !== '') {
    echo json_encode(['success' => true, 'message' => 'Dziękuję!'], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$service = trim((string)($_POST['service'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$gdpr = (string)($_POST['gdpr'] ?? '');

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
    $errors[] = 'Podaj imię i nazwisko.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    $errors[] = 'Podaj poprawny adres e-mail.';
}
if ($phone !== '' && mb_strlen($phone) > 30) {
    $errors[] = 'Niepoprawny numer telefonu.';
}
if ($service === '' || mb_strlen($service) > 80) {
    $errors[] = 'Wybierz rodzaj sesji.';
}
if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
    $errors[] = 'Wiadomość musi mieć od 10 do 2000 znaków.';
}
if ($gdpr !== '1') {
    $errors[] = 'Wymagana zgoda RODO.';
}

if ($errors) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Brak zależności PHPMailer. Uruchom: composer install',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . '/config.php';
$smtp = (array)($config['mail']['smtp'] ?? []);

$smtpHost = (string)($smtp['host'] ?? '');
$smtpPort = (int)($smtp['port'] ?? 587);
$smtpUser = (string)($smtp['user'] ?? '');
$smtpPass = (string)($smtp['pass'] ?? '');
$smtpSecure = (string)($smtp['secure'] ?? 'tls'); // tls|ssl|none

$fromEmail = (string)($smtp['from_email'] ?? $smtpUser);
$fromName = (string)($smtp['from_name'] ?? 'EcoShot – formularz');
$toEmail = (string)($smtp['to_email'] ?? '');
$toName = (string)($smtp['to_name'] ?? 'EcoShot');

if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '' || $toEmail === '' || $fromEmail === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Brak konfiguracji SMTP. Ustaw zmienne środowiskowe SMTP_*.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->Port = $smtpPort;

    if ($smtpSecure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtpSecure === 'none') {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail, $toName);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Nowe zapytanie – formularz kontaktowy';

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

    $mail->Body = "
      <h2>Nowe zapytanie z formularza</h2>
      <p><strong>Imię i nazwisko:</strong> " . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <p><strong>E-mail:</strong> " . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <p><strong>Telefon:</strong> " . htmlspecialchars($phone ?: '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <p><strong>Rodzaj sesji:</strong> " . htmlspecialchars($service, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <hr>
      <p><strong>Wiadomość:</strong><br>" . $safeMessage . "</p>
      <hr>
      <p style=\"color:#6b7280;font-size:12px\">IP: " . htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "<br>UA: " . htmlspecialchars($ua, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
    ";

    $mail->AltBody = "Nowe zapytanie\n\n" .
        "Imię i nazwisko: {$name}\n" .
        "E-mail: {$email}\n" .
        "Telefon: " . ($phone ?: '—') . "\n" .
        "Rodzaj sesji: {$service}\n\n" .
        "Wiadomość:\n{$message}\n";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Dziękuję! Wiadomość wysłana. Odpowiem najszybciej, jak to możliwe.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Nie udało się wysłać wiadomości. Spróbuj ponownie lub napisz bezpośrednio.',
    ], JSON_UNESCAPED_UNICODE);
}
