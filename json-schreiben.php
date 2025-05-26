<?php

<<<<<<< HEAD
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

=======
>>>>>>> ef42392efe8500501dc97ed47958140ea4700e4c
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

<<<<<<< HEAD
// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Headers setzen
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // JSON-Daten aus dem POST-Request lesen
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput === false) {
        throw new Exception('Keine POST-Daten empfangen');
    }

    $input = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Ungültiges JSON Format: ' . json_last_error_msg());
    }

    // Prüfen ob Daten und Dateiname übergeben wurden
    if (!$input || !isset($input['data']) || !isset($input['filename'])) {
        throw new Exception('Keine gültigen Daten oder Dateiname übermittelt');
    }

    // JSON schreiben
    $jsonData = json_encode($input['data'], 
        JSON_PRETTY_PRINT | 
        JSON_UNESCAPED_UNICODE | 
        JSON_UNESCAPED_SLASHES
    );
    
    if ($jsonData === false) {
        throw new Exception('JSON Encodierung fehlgeschlagen: ' . json_last_error_msg());
    }

    // In Datei schreiben
    if (file_put_contents($input['filename'], $jsonData) === false) {
        throw new Exception('Fehler beim Schreiben der Datei');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>

=======

// JSON-Daten aus dem POST-Request lesen
$jsonInput = file_get_contents('php://input');
$input = json_decode($jsonInput, true);

// Prüfen, ob Daten und Dateiname übergeben wurden
if ($input && isset($input['data']) && isset($input['filename'])) {
    $data = $input['data'];
    $filename = $input['filename'];

    $result = writeArrayToJSON($data, $filename);

    echo json_encode(['success' => $result]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Keine gültigen Daten oder Dateiname übermittelt'
    ]);
}

function writeArrayToJSON($data, $filename) {
    if (empty($data) || !is_array($data)) {
        return false;
    }

    try {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Optional: füge .json-Endung hinzu, falls nicht vorhanden
        if (!str_ends_with($filename, '.json')) {
            $filename .= '.json';
        }

        return file_put_contents($filename, $jsonData) !== false;
    } catch (Exception $e) {
        return false;
    }
}
?>
>>>>>>> ef42392efe8500501dc97ed47958140ea4700e4c
