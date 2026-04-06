<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/auth.php';

try {
    $config = loadKasseConfig();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Konfiguration konnte nicht geladen werden.']);
    exit;
}

$token = (string)($_COOKIE[authCookieName()] ?? '');
if (!validateAuthToken($token, $config)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert.']);
    exit;
}

$file = (string)($_GET['file'] ?? '');
$allowed = [
    'produkte.json',
    'kunden.json',
    'externe.json',
];

if (!in_array($file, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Datei.']);
    exit;
}

$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'daten' . DIRECTORY_SEPARATOR . $file;
if (!is_file($path)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Datei nicht gefunden.']);
    exit;
}

$raw = file_get_contents($path);
if ($raw === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datei konnte nicht gelesen werden.']);
    exit;
}

echo $raw;
