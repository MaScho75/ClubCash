<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/auth.php';

try {
    $config = loadKasseConfig();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['authenticated' => false, 'error' => 'Konfiguration konnte nicht geladen werden.']);
    exit;
}

$token = (string)($_COOKIE[authCookieName()] ?? '');
if (!validateAuthToken($token, $config)) {
    clearAuthCookie();
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'error' => 'Nicht autorisiert.']);
    exit;
}

echo json_encode(['authenticated' => true]);
