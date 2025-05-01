<?php 
session_start();
require_once 'VereinsfliegerRestInterface.php';

// Lese die .env-Datei
$env = parse_ini_file('daten/.env');  // Lädt die Umgebungsvariablen aus der .env-Datei

// Lade die Kunden-Daten aus der JSON-Datei
$kundenDaten = json_decode(file_get_contents('daten/kunden.json'), true);

// Prüfen, ob der Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: portal.php'); // Die geschützte Seite im Hauptverzeichnis
    exit();
}

// Falls das Login-Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $KundenName = trim($_POST['kundenname'] ?? '');
    $Schlüsselnummer = trim($_POST['schlüsselnummer'] ?? '');

    if (!empty($KundenName) && !empty($Schlüsselnummer)) {
        $found = false;
        foreach ($kundenDaten as $kunde) {
            if ($kunde['email'] === $KundenName && $kunde['key2designation'] === $Schlüsselnummer) {
                $_SESSION['user_authenticated'] = true;
                $_SESSION['username'] = $KundenName;
                $_SESSION['customer_login'] = true;
                header('Location: portal.php');
                exit();
            }
        }
        $error_message = "Ungültige Email oder Schlüsselnummer!";
    } else {
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

                <div class="grid-container" style="display: grid; grid-template-columns: auto auto; gap: 10px; padding: 20px;" >

                    <p></p><p style="margin: 0px; "><b>Kunden-Login</b></p>

                    <div style=" padding: 5px; text-align: right; width: 250px;">Email</div>

                    <div style=" padding: 5px; text-align: center;"><input type="text" name="kundenname" id="kundenname" style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>

                    <div style=" padding: 5px; text-align: right;">Key</div>

                    <div style=" padding: 5px; text-align: center;"><input type="password" name="schlüsselnummer" id="schlüsselnummer" style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
                    
                </div>

            <div style="text-align: center;">
                <input class="button" type="submit" value="Anmelden">
                <br>
                <button class="button" type="button" onclick="window.location.href='admin.php';">Admin-Login</button>
            </div>



        </form>
    </div>
</body>
</html>
