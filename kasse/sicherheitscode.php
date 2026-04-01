<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST erlaubt.']);
    exit;
}

try {
    $config = loadKasseConfig();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Konfiguration konnte nicht geladen werden.']);
    exit;
}

$inputCode = '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $body = file_get_contents('php://input');
    $parsed = is_string($body) ? json_decode($body, true) : null;
    if (is_array($parsed)) {
        $inputCode = trim((string)($parsed['code'] ?? ''));
    }
} else {
    $inputCode = trim((string)($_POST['code'] ?? ''));
}

if (!verifyKassenPw($inputCode, $config)) {
    usleep(200000);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Falscher Sicherheitscode!']);
    exit;
}

try {
    $token = createAuthToken($config);
    setAuthCookie($token);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Token konnte nicht erstellt werden!']);
    exit;
}

echo json_encode([
    'success' => true,
    'token' => $token,
    'redirect' => 'index.php',
]);
