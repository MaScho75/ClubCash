<?php
/**
 * Schreibt ein Array in eine CSV-Datei
 * @param array $data Das zu schreibende Array
 * @param string $filename Der Name der CSV-Datei
 * @return bool True bei Erfolg, False bei Fehler
 */

// JSON-Daten aus dem POST-Request lesen
$jsonInput = file_get_contents('php://input');
$input = json_decode($jsonInput, true);

// Prüfen ob Daten und Dateiname übergeben wurden
if ($input && isset($input['data']) && isset($input['filename'])) {
    $result = writeArrayToCSV($input['data'], $input['filename']);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Keine gültigen Daten oder Dateiname übermittelt'
    ]);
}

writeArrayToCSV($data, $filename);

function writeArrayToCSV($data, $filename) {
    if (empty($data) || !is_array($data)) {
        return false;
    }

    try {
        // Öffne die Datei zum Schreiben
        $fp = fopen($filename, 'w');
        
        // Schreibe BOM für Excel UTF-8 Erkennung
        //fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

        // Schreibe die Kopfzeile mit den Array-Keys
        $headers = array_keys($data[0]);
        fputcsv($fp, $headers, ';');

        // Schreibe die Daten
        foreach ($data as $row) {
            fputcsv($fp, $row, ';');
        }

        fclose($fp);
        return true;
    } catch (Exception $e) {
        return false;
    }
}


?>