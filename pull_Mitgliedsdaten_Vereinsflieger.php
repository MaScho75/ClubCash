<?php
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

// Lade die Datei config.js
$configJs = file_get_contents('config.js');

// Entferne "const config =" und Semikolon
$configJs = preg_replace('/const config =|;/', '', $configJs);

// Entferne JavaScript-Kommentare (einzeilig & mehrzeilig)
$configJs = preg_replace('/\/\/[^\n]*|\/\*.*?\*\//s', '', $configJs);

// JSON korrekt parsen (Kommentare sind jetzt weg)
$config = json_decode(trim($configJs), true);

if (!$config) {
    die("<pre>❌ Fehler beim Laden der Konfigurationsdatei: " . json_last_error_msg() . "</pre>");
}

echo "<p>✅ Konfigurationsdatei erfolgreich geladen.</p>";

// Lese die .env-Datei
$env = parse_ini_file('daten/.env');  // Lädt die Umgebungsvariablen aus der .env-Datei

// Wrapper-Datei einbinden
require_once 'VereinsfliegerRestInterface.php';

// Anmeldeinformationen
$UserName = $env['USERNAME'];
$Password = $env['PASSWORT'];
$AppKey = $env['APPKEY'];
$AuthSecret = $env['AUTRHSECRET'];

// VereinsfliegerRestInterface-Instanz erstellen
$restInterface = new VereinsfliegerRestInterface();


// Anmeldung durchführen
if ($restInterface->SignIn($UserName, $Password, 0, $AppKey, $AuthSecret)) {
    echo "<p>✅ Anmeldung in Vereinsflieger war erfolgreich.</p>\n";

    // Nutzer abrufen
    if ($restInterface->GetUsers()) {
        echo "<p>✅ Die Mitgliederdaten wurden erfolgreich aus Vereinsflieger.de abgerufen.<p>\n";

        // Abgerufene Nutzerdaten holen
        $usersData = $restInterface->getResponse();

        // Nutzer filtern und verarbeiten
        $filteredUsers = array_filter(array_map(function($user) use ($config) {
            if (!empty($user['key2designation'])) {
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
                    'key2designation' => $user['key2designation'] ?? null,
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
} else {
    echo "<p>❌ Anmeldung fehlgeschlagen.</p>\n";
}

?>
