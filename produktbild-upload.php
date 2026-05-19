<?php

/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 */

session_start();

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert.']);
    exit();
}

header('Content-Type: application/json');

$ean = preg_replace('/\D/', '', (string)($_POST['ean'] ?? ''));
if ($ean === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungueltige EAN.']);
    exit();
}

$targetDir = __DIR__ . DIRECTORY_SEPARATOR . 'Produktbilder';
$target = $targetDir . DIRECTORY_SEPARATOR . $ean . '.png';

if (($_POST['action'] ?? '') === 'delete') {
    if (is_file($target) && !unlink($target)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Bild konnte nicht geloescht werden.']);
        exit();
    }
    echo json_encode(['success' => true]);
    exit();
}

if (!isset($_FILES['produktbild']) || $_FILES['produktbild']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Keine Bilddatei empfangen.']);
    exit();
}

$upload = $_FILES['produktbild'];
if ((int)$upload['size'] > 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bilddatei ist zu gross.']);
    exit();
}

$imageInfo = getimagesize($upload['tmp_name']);
if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== 'image/png') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Das Bild muss als PNG uebertragen werden.']);
    exit();
}

if ((int)$imageInfo[0] !== 200 || (int)$imageInfo[1] !== 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Das Bild muss 200x200 Pixel gross sein.']);
    exit();
}

if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Produktbilder-Ordner konnte nicht erstellt werden.']);
    exit();
}

if (!move_uploaded_file($upload['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Bild konnte nicht gespeichert werden.']);
    exit();
}

echo json_encode([
    'success' => true,
    'file' => 'Produktbilder/' . $ean . '.png',
]);

?>
