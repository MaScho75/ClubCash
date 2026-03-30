<?php

/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * ClubCash is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with ClubCash. If not, see <https://www.gnu.org/licenses/>.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Nur POST ist erlaubt.'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$datensatz = '';
if (is_array($input) && isset($input['datensatz'])) {
    $datensatz = (string) $input['datensatz'];
} elseif (isset($_POST['datensatz'])) {
    $datensatz = (string) $_POST['datensatz'];
}

$produktBezeichnung = '';
if (is_array($input) && isset($input['produktBezeichnung'])) {
    $produktBezeichnung = (string) $input['produktBezeichnung'];
} elseif (isset($_POST['produktBezeichnung'])) {
    $produktBezeichnung = (string) $_POST['produktBezeichnung'];
}

$produktEAN = '';
if (is_array($input) && isset($input['produktEAN'])) {
    $produktEAN = (string) $input['produktEAN'];
} elseif (isset($_POST['produktEAN'])) {
    $produktEAN = (string) $_POST['produktEAN'];
}

$zaehlerstandNeuRaw = null;
if (is_array($input) && isset($input['zaehlerstandNeu'])) {
    $zaehlerstandNeuRaw = $input['zaehlerstandNeu'];
} elseif (isset($_POST['zaehlerstandNeu'])) {
    $zaehlerstandNeuRaw = $_POST['zaehlerstandNeu'];
}

$datensatz = str_replace(["\r", "\n"], ' ', trim($datensatz));

if ($datensatz === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Kein Datensatz übergeben.'
    ]);
    exit;
}

$produktBezeichnung = trim($produktBezeichnung);
$produktEAN = trim($produktEAN);
$zaehlerstandNeu = is_numeric($zaehlerstandNeuRaw) ? (float) $zaehlerstandNeuRaw : null;

if ($zaehlerstandNeu === null) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Ungültiger Zählerstand übergeben.'
    ]);
    exit;
}

if ($produktBezeichnung === '' && $produktEAN === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Kein Produkt zur Zählerstand-Aktualisierung übergeben.'
    ]);
    exit;
}

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'daten';
if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Verzeichnis daten konnte nicht erstellt werden.'
    ]);
    exit;
}

$csvFile = $dataDir . DIRECTORY_SEPARATOR . 'umsatz.csv';
$line = PHP_EOL . $datensatz;

if (file_put_contents($csvFile, $line, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datensatz konnte nicht gespeichert werden.'
    ]);
    exit;
}

$produkteFile = $dataDir . DIRECTORY_SEPARATOR . 'produkte.json';
if (!is_file($produkteFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datei produkte.json nicht gefunden.'
    ]);
    exit;
}

$produkteRaw = file_get_contents($produkteFile);
$produkte = json_decode((string) $produkteRaw, true);

if (!is_array($produkte)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'produkte.json enthält kein gültiges Array.'
    ]);
    exit;
}

$produktGefunden = false;
foreach ($produkte as &$produkt) {
    if (!is_array($produkt)) {
        continue;
    }

    $eanMatch = ($produktEAN !== '' && isset($produkt['EAN']) && (string) $produkt['EAN'] === $produktEAN);
    $bezeichnungMatch = ($produktBezeichnung !== '' && isset($produkt['Bezeichnung']) && (string) $produkt['Bezeichnung'] === $produktBezeichnung);

    if ($eanMatch || $bezeichnungMatch) {
        $produkt['Zählerstand'] = $zaehlerstandNeu;
        $produktGefunden = true;
        break;
    }
}
unset($produkt);

if (!$produktGefunden) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Passendes Produkt für Zählerstand-Aktualisierung nicht gefunden.'
    ]);
    exit;
}

$produkteJson = json_encode($produkte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($produkteJson === false || file_put_contents($produkteFile, $produkteJson, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'produkte.json konnte nicht aktualisiert werden.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Datensatz gespeichert und Zählerstand aktualisiert.'
]);
