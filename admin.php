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
require_once 'VereinsfliegerRestInterface.php';

// Prüfen, ob der Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: portal.php');
    exit();
}

// // Lese die .env-Datei mit Fehlerbehandlung
// $envFile = 'daten/.env';
// if (!file_exists($envFile)) {
//     die("Fehler: .env Datei nicht gefunden in: $envFile");
// }

// $env = parse_ini_file($envFile);
// if ($env === false) {
//     die("Fehler beim Lesen der .env Datei");
// }


// Lade config.json
$config = json_decode(file_get_contents('daten/config.json'), true);
if ($config === null) {
    die('Fehler beim Lesen der config.json');
}

// Falls das Login-Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $UserName = trim($_POST['username'] ?? '');
    $Password = trim($_POST['password'] ?? '');
    $Authentifizier = trim($_POST['authentifizier'] ?? '');

    if (!empty($UserName) && !empty($Password)) {
        $api = new VereinsfliegerRestInterface();
        
        if ($api->SignIn($UserName, $Password, 0, $config['appkey'], $Authentifizier)) {
            // Login erfolgreich - Token speichern
            $_SESSION['accessToken'] = $api->GetAccessToken();
            $_SESSION['tokenExpiry'] = time() + 3600; // Token für 1 Stunde gültig
            $_SESSION['user_authenticated'] = true;
            $_SESSION['username'] = $UserName;
            $_SESSION['customer_login'] = false;

            header('Location: portal.php');
            exit();
        } else {
            $error_message = "Ungültiger Benutzername/ungültiges Passwort/ungültige Authentifizierung!<br>Bitte gebe deine Zugangsdaten von Vereinsflieger.de ein.<br>Wenn du keinen Zugang hast, wende dich bitte an den Administrator.";
        }
    } else {
        $error_message = "Bitte Benutzername und Passwort und ggf. den temporären Authentifizierungscode eingeben.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Zeichensatz auf UTF-8 setzen -->
    <meta charset="UTF-8">

    <!-- Skalierbarkeit für mobile Geräte sicherstellen -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ClubCashAdminLogin</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

</head>
<body class="portal">
    <div id="login-container">
       <div id="kopf" style="display: block; align-items: center;">
            <a href="https://clubcash.net/"><img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; "></a>

        <p><b><a href="<?php echo $config['Webseite']; ?>" target="_blank" style="text-decoration: none; margin: 0px;">
                <span style="font-size: 24px; color: var(--warning-color););"><?php echo $config['Vereinsname']; ?></span>
            </a></b></p>
        <p><b>Admin-Login</b></p>
        </div>
        <?php if (isset($error_message)) echo "<p style='text-align: center; color: var(--error-color);'>$error_message</p>"; ?>
        
        <form method="POST" action="">

                <div class="grid-container" style="display: grid; grid-template-columns: auto auto; gap: 10px; margin-bottom: 20px;">

                    

                    <div style=" padding: 5px; text-align: right; width: 250px;">Email</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="text" name="username" id="username"  style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
                    
                    <div style=" padding: 5px; text-align: right;">Passwort</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="password" name="password" id="password"  style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
                    
                    <div style=" padding: 5px; text-align: right;">Zwei-Faktor-Authentifizierung</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="text" name="authentifizier" id="authentifizier" placeholder="optional" style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
            
                </div>

            <div style="text-align: center;">
                <input class="green button" type="submit" value="Anmelden">
                <br>
                <button class="button" type="button" onclick="window.location.href='index.php';">Kunden-Login</button>
            </div>

        </form>
    </div>
</body>
</html>
