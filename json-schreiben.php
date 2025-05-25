<?php
<<<<<<< HEAD
<<<<<<< HEAD
=======

>>>>>>> 6b6330bacad7d7d05b2ef09e764d17d481c980ca
=======

>>>>>>> 6b6330bacad7d7d05b2ef09e764d17d481c980ca
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

<<<<<<< HEAD
<<<<<<< HEAD
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
=======
=======
>>>>>>> 6b6330bacad7d7d05b2ef09e764d17d481c980ca
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

<<<<<<< HEAD
?>
>>>>>>> 6b6330bacad7d7d05b2ef09e764d17d481c980ca
=======
?>
>>>>>>> 6b6330bacad7d7d05b2ef09e764d17d481c980ca
