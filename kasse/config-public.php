<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';

try {
    $config = loadKasseConfig();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Konfiguration konnte nicht geladen werden.']);
    exit;
}

$sensitiveKeys = [
    'Sicherheitscode',
    'appkey',
    'kassenpw',
    'SMTPPasswort',
];

foreach ($sensitiveKeys as $key) {
    unset($config[$key]);
}

echo json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
