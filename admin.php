<?php 
session_start();
require_once 'VereinsfliegerRestInterface.php';

// Lese die .env-Datei
$env = parse_ini_file('daten/.env');  // Lädt die Umgebungsvariablen aus der .env-Datei


// Prüfen, ob der Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: portal.php'); // Die geschützte Seite im Hauptverzeichnis
    exit();
}

// Falls das Login-Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $UserName = trim($_POST['username'] ?? '');
    $Password = trim($_POST['password'] ?? '');
	$Authentifizier = trim($_POST['authentifizier'] ?? '');

    if (!empty($UserName) && !empty($Password)) {
        $api = new VereinsfliegerRestInterface();
        $AppKey = $env['APPKEY']; // AppKey aus der .env-Datei
        $AuthSecret = $Authentifizier; // 2FA

        if ($api->SignIn($UserName, $Password, 0, $AppKey, $AuthSecret)) {
            $_SESSION['user_authenticated'] = true;
            $_SESSION['username'] = $UserName; // Optional zur Anzeige im Dashboard
            $_SESSION['customer_login'] = false; // Optional zur Anzeige im Dashboard

            header('Location: portal.php'); // Weiterleitung zur geschützten Seite
            exit();
        } else {
            $error_message = "Ungültiger Benutzername/ungültiges Passwort/ungültige Authentifizierung!<br>Bitte gebe deine Zugangsdaten von Vereinsflieger.de ein.<br>Wenn du keinen Zugang hast, wende dich bitte an den Administrator.";
        }

    }  else {
        $error_message = "Bitte Benutzername und Passwort und ggf. den temprären Authentifizierungscode eingeben.";
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

    <title>Cafè Lüsse Login</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
	
	<link href="https://fonts.googleapis.com/css2?family=Carlito&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

</head>
<body class="portal">
    <div id="login-container">
       <div id="kopf" style="display: flex; align-items: center;">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">	   
        </div>
        
        <?php if (isset($error_message)) echo "<p style='text-align: center; color: var(--error-color);'>$error_message</p>"; ?>
        
        <form method="POST" action="">

                <div class="grid-container" style="display: grid; grid-template-columns: auto auto; gap: 10px; margin-bottom: 20px;">

                    <p></p><p style="margin: 0px; "><b>Admin-Login</b></p>

                    <div style=" padding: 5px; text-align: right; width: 250px;">Email</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="text" name="username" id="username"  style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
                    
                    <div style=" padding: 5px; text-align: right;">Passwort</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="password" name="password" id="password"  style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
                    
                    <div style=" padding: 5px; text-align: right;">Zwei-Faktor-Authentifizierung</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="text" name="authentifizier" id="authentifizier" placeholder="optional" style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
            
                </div>

            <div style="text-align: center;">
                <input class="button" type="submit" value="Anmelden">
                <br>
                <button class="button" type="button" onclick="window.location.href='index.php';">Kunden-Login</button>
            </div>

        </form>
    </div>
</body>
</html>
