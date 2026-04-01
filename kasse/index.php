<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

try {
    $config = loadKasseConfig();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Konfiguration konnte nicht geladen werden.';
    exit;
}

$token = (string)($_COOKIE[authCookieName()] ?? '');
if (!validateAuthToken($token, $config)) {
    clearAuthCookie();
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $target = 'login.php';
    if ($query !== '') {
        $target .= '?redirect=' . urlencode('index.php?' . $query);
    }
    header('Location: ' . $target);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/index.html');
