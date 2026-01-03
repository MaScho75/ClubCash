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

session_start();

// Prüfen, ob der Benutzer eingeloggt ist

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = json_decode(file_get_contents(__DIR__ . '/daten/config.json'), true);

$mail = new PHPMailer(true);

//übergebene Variablen einlesen
$input = json_decode(file_get_contents('php://input'), true);

//fehlerbehandlung
if (!isset($input['kundennummer']) || !isset($input['name']) || !isset($input['vorname']) || !isset($input['email']) || !isset($input['datum1']) || !isset($input['datum2']) || !isset($input['html'])) {
    echo 'FEHLER: Fehlende Parameter.';
    exit();
}

try {
    // Debug-Modus nur für Entwicklung (auskommentieren für Produktion)
    // $mail->SMTPDebug = 2;

    $mail->isSMTP();
    $mail->Host       = $config['SMTPServer'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['SMTPBenutzer'];
    $mail->Password   = $config['SMTPPasswort'];

    $enc = strtolower($config['SMTPVerschluesselung'] ?? 'tls');
    if ($enc === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;   // 465
        $mail->Port = 465;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 587
        $mail->Port = 587;
    }
    
    // Port aus Config überschreiben, falls anders konfiguriert
    if (!empty($config['SMTPPort'])) {
        $mail->Port = (int)$config['SMTPPort'];
    }

    // Zeichenkodierung auf UTF-8 setzen
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $mail->setFrom($config['SMTPAbsenderadresse'], $config['VereinsnameAbk'] ?? 'ClubCash');
    $mail->addReplyTo($config['SMTPAntwortadresse']);

    $mail->addAddress($input['email']);
    $mail->addBCC($config['SMTPAbsenderadresse']); 

    $mail->isHTML(true);
    $mail->Subject = 'ClubCash Abrechnung für ' . $input['vorname'] . ' ' . $input['name'] . ' (' . $input['kundennummer'] . ')';
    $mail->Body    = $input['html'];
    $mail->AltBody = 'Abrechnung für ' . $input['vorname'] . ' ' . $input['name'] . ' (' . $input['kundennummer'] . ')';

    // HTML als Dateianhang hinzufügen, wenn gewünscht
    if (isset($input['anhang']) && $input['anhang'] === true && !empty($input['html'])) {
        // Temporäre HTML-Datei erstellen
        $tempDir = sys_get_temp_dir();
        
        // Dateiname bereinigen (Sonderzeichen entfernen)

        $cleanKundennummer = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['kundennummer']);
        $filename = 'ClubCash-Abrechnung_Mitgliedsnummer-' . $cleanKundennummer . '_' . $input['datum1'] . '_bis_' . $input['datum2'] . '.html';
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . $filename;
        
        // HTML-Inhalt in Datei schreiben mit UTF-8 Encoding und Fehlerprüfung
        $bytesWritten = file_put_contents($tempFile, "\xEF\xBB\xBF" . $input['html']);
        
        if ($bytesWritten === false) {
            error_log("Fehler: Konnte temporäre HTML-Datei nicht erstellen: " . $tempFile);
        } elseif (!file_exists($tempFile)) {
            error_log("Fehler: Temporäre Datei wurde nicht erstellt: " . $tempFile);
        } else {
            // Datei als Anhang hinzufügen (ohne explizites encoding und mime-type - PHPMailer erkennt das automatisch)
            $mail->addAttachment($tempFile, $filename);
        }
    }

    $mail->send();
    
    // Temporäre Datei nach erfolgreichem Versand löschen
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    echo 'OK: E-Mail mit Rechnung wurde an ' . htmlspecialchars($input['email']) . ' gesendet.';
} catch (Exception $e) {

    // Temporäre Datei auch im Fehlerfall löschen
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
    echo 'FEHLER: ' . htmlspecialchars($mail->ErrorInfo);
}
?>