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


// Define config file path
$configFile = 'daten/config.json';

// Create daten directory if it doesn't exist
if (!is_dir('daten')) {
    mkdir('daten', 0755, true);
}

// Create or load config file
if (!file_exists($configFile)) {
    $configData = ['appkey' => ''];
} else {
    $configData = json_decode(file_get_contents($configFile), true);
    if ($configData === null) {
        $configData = ['appkey' => ''];
    }
}

//prüfen , ob appkey eingegeben wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appkey = trim($_POST['appkey'] ?? '');
    if (!empty($appkey)) {
        // App-Key speichern
        $configData['appkey'] = $appkey;
        if (file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            die("Fehler beim Speichern der Konfigurationsdatei");
        }
        header('Location: index.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubCash Initialisierung</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="farben.css?v=<?= time(); ?>">
</head>
<body class="portal">
    <div id="login-container" style="font-size: .8em;">
        <div id="kopf" style="display: flex; align-items: center;">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; margin: 30px;">
        </div>
        <h2>Initialisierung</h2>
       <p>Bitte generieren Sie bei Vereinsflieger einen APPKEY und geben Sie in hier ein. Ohne den APPKEY können Sie ClubCash nicht nutzen.</p>
        <p>Der APPKEY ist eine Art Schlüssel, der es ClubCash ermöglicht, sicher mit dem Vereinsflieger.de-System zu kommunizieren.</p>
        <p>Danach können Sie sich bei ClubCash mit Ihren Zugangsdaten von Vereinsflieger.de anmelden. Wechsels Sie dazu beim Aufrufen der Seite
            auf Admin-Login.</p>
        <p>Sollten Sie die falsche App-Key eingegeben haben, können Sie sich nicht anmelden. In diesem Fall müssen Sie den Ordner "daten" löschen oder diese Seite mit dem Zusatz "/install.php" aufrufen. Dann 
            können Sie einen neuen App-Key eingeben.</p>

        <form id="appkeyForm" method="post" action="install.php">
            <label for="appkey">App-Key:</label>
            <input style="width: 240px; text-align: center;" type="text" id="appkey" name="appkey" required><br>
            <button class="button"  type="submit">speichern</button>
        </form>

    </div>
</body>
</html>
