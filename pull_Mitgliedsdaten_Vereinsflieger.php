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

// Fehleranzeige aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Lade die Datei config.json
    $configFile = 'daten/config.json';
if (!file_exists($configFile)) {
    die("<pre>❌ Konfigurationsdatei nicht gefunden: $configFile</pre>");
}
$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    die("<pre>❌ Fehler beim Laden der Konfigurationsdatei: " . json_last_error_msg() . "</pre>");
}

echo "<p>✅ Konfigurationsdatei erfolgreich geladen.</p>";

echo "<p>Die Spalte für die Bezahlschlüssel in der Mitgliederverwaltung in Vereinsflieger ist: <strong>" . htmlspecialchars($config['schlüssel']) . "</strong></p>\n";

$schlüsselbezeichnung = $config['schlüssel'] ?? null;

if (!$schlüsselbezeichnung) {
    die("<pre>❌ Kein Bezahlschlüssel in der Konfigurationsdatei gefunden.</pre>");
}

echo "<p>✅ Umgebungsvariablen erfolgreich geladen.</p>";

// Wrapper-Datei einbinden
require_once 'VereinsfliegerRestInterface.php';

// VereinsfliegerRestInterface-Instanz erstellen
$restInterface = new VereinsfliegerRestInterface();

// Token-Handling
if (isset($_SESSION['accessToken']) && isset($_SESSION['tokenExpiry']) && $_SESSION['tokenExpiry'] > time()) {
    // Bestehenden Token weiterverwenden
    $restInterface->SetAccessToken($_SESSION['accessToken']);
    echo "<p>✅ Bestehender Token wiederverwendet.</p>\n";
    
    // Nutzer direkt abrufen
    if ($restInterface->GetUsers()) {
        echo "<p>✅ Die Mitgliederdaten wurden erfolgreich aus Vereinsflieger.de abgerufen.<p>\n";

        // Abgerufene Nutzerdaten holen
        $usersData = $restInterface->getResponse();

        // Nutzer filtern und verarbeiten
        $filteredUsers = array_filter(array_map(function($user) use ($config, $schlüsselbezeichnung) {

            if (!empty($user[$schlüsselbezeichnung])) {
                // Überprüfen, ob 'roles' ein Array ist oder ein JSON-String
                $roles = isset($user['roles']) ? (is_array($user['roles']) ? $user['roles'] : json_decode($user['roles'], true)) : [];
        
                // Falls json_decode fehlschlägt (z.B. wenn es kein JSON-String war), setzen wir ein leeres Array
                if (!is_array($roles)) {
                    $roles = [];
                }

                return [
                    'uid' => $user['uid'] ?? null,
                    'firstname' => $user['firstname'] ?? null,
                    'lastname' => $user['lastname'] ?? null,
                    'email' => $user['email'] ?? null,
                    'memberid' => $user['memberid'] ?? null,
                    'schlüssel' => $user[$schlüsselbezeichnung] ?? null,
                    'roles' => json_encode($roles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), // JSON korrekt formatieren
                    'cc_admin' => in_array($config['cc_admin'], $roles, true),
                    'cc_seller' => in_array($config['cc_seller'], $roles, true),
                    'cc_member' => in_array($config['cc_member'], $roles, true),
                    'cc_guest' => in_array($config['cc_guest'], $roles, true),
                ];
            }
            return null;
        }, $usersData));
        // Alle null-Werte aus dem Array entfernen
        $filteredUsers = array_filter($filteredUsers);

        // Sicherstellen, dass die JSON-Datei eine einfache Liste ohne numerische Keys enthält
        $jsonData = json_encode(array_values($filteredUsers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Die gefilterten Daten in eine JSON-Datei speichern
        $jsonFile = 'daten/kunden.json';

        if (file_put_contents($jsonFile, $jsonData)) {
            echo "<p>✅ Die Daten wurden erfolgreich von Vereinsflieger.de in das Kassensystem importiert.</p>\n";
        } else {
            echo "<p>❌ Fehler beim Speichern der Daten aus Vereinsflieger.de.</p>\n";
        }
    } else {
        echo "<p>❌ Fehler beim Abrufen der Daten.</p>\n";
    }
    echo "<p>Bitte aktualisiere die Seite, um die Änderungen zu sehen.</p>\n";
    echo "<button class='kleinerBt' onclick=\"window.location.href='index.php'\">Startseite</button>\n";

} else {
    // Kein gültiger Token vorhanden - Benutzer abmelden
    session_destroy();
    header('Location: index.php');
    exit();
}

?>
