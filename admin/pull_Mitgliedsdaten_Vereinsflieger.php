<?php

// Lese die .env-Datei
$env = parse_ini_file('.env');  // Lädt die Umgebungsvariablen aus der .env-Datei

// Wrapper-Datei einbinden
require_once 'VereinsfliegerRestInterface.php';

// Anmeldeinformationen (werden aus der .env-Datei geladen)
$UserName = $env['USERNAME'];
$Password = $env['PASSWORT'];
$AppKey = $env['APPKEY'];
$AuthSecret = $env['AUTRHSECRET'];

// VereinsfliegerRestInterface-Instanz erstellen
$restInterface = new VereinsfliegerRestInterface();

// Anmeldung durchführen
if ($restInterface->SignIn($UserName, $Password, 0, $AppKey, $AuthSecret)) {
    echo "<p>Anmeldung in Vereinsflieger war erfolgreich.</p>\n";

    // Nutzer abrufen
    if ($restInterface->GetUsers()) {
        echo "<p>Die Mitgliederdaten wurden erfolgreich aus Vereinsflieger.de abgerufen.<p>\n";

        // Abgerufene Nutzerdaten holen
        $usersData = $restInterface->getResponse();

        // Nur die gewünschten Felder extrahieren und nur speichern, wenn 'key2designation' einen Wert hat
        $filteredUsers = array_filter(array_map(function($user) {
            if (!empty($user['key2designation'])) {
                return [
                    'uid' => $user['uid'] ?? null,
                    'firstname' => $user['firstname'] ?? null,
                    'lastname' => $user['lastname'] ?? null,
                    'email' => $user['email'] ?? null,
                    'key2designation' => $user['key2designation'] ?? null
                ];
            }
            return null;
        }, $usersData));

        // Alle null-Werte aus dem Array entfernen
        $filteredUsers = array_filter($filteredUsers);

        // Sicherstellen, dass die JSON-Datei eine einfache Liste ohne numerische Keys enthält
        $jsonData = json_encode(array_values($filteredUsers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Die gefilterten Daten in eine JSON-Datei speichern
        $jsonFile = '../daten/kunden.json';

        if (file_put_contents($jsonFile, $jsonData)) {
            echo "<p>Die Daten wurden erfolgreich von Vereinsflieger.de in das Kassensystem importiert.</p>\n";
        } else {
            echo "<p>Es ist ein Fehler beim Speichern der Daten aus Vereinsflieger.de aufgetreten.</p>\n";
        }
    } else {
        echo "<p>Es ist ein Fehler beim Abrufen der Daten aus Vereinsflieger.de aufgetreten. Status Code: " . $restInterface->HttpStatusCode . "</p>\n";
    }
} else {
    echo "<p>Es ist ein Fehler beim Abrufen der Daten aus Vereinsflieger.de aufgetreten. Die Anmeldung ist fehlgeschlagen.</p>\n";
}

?>
