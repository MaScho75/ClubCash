<?php
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

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
