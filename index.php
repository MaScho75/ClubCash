<?php
session_start();
require_once 'admin/VereinsfliegerRestInterface.php';

// Prüfen, ob der Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: portal.php'); // Die geschützte Seite im Hauptverzeichnis
    exit();
}

// Falls das Login-Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $UserName = trim($_POST['username'] ?? '');
    $Password = trim($_POST['password'] ?? '');

    if (!empty($UserName) && !empty($Password)) {
        $api = new VereinsfliegerRestInterface();
        $AppKey = '18d628a4f80943182117f041e1c417b3';
        $AuthSecret = ''; // Falls erforderlich

        if ($api->SignIn($UserName, $Password, 0, $AppKey, $AuthSecret)) {
            $_SESSION['user_authenticated'] = true;
            $_SESSION['username'] = $UserName; // Optional zur Anzeige im Dashboard

            header('Location: portal.php'); // Weiterleitung zur geschützten Seite
            exit();
        } else {
            $error_message = "Ungültiger Benutzername oder Passwort!";
        }
    } else {
        $error_message = "Bitte Benutzername und Passwort eingeben.";
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

    <title>Cafè Lüsse Zugangsportal</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
	
	<link href="https://fonts.googleapis.com/css2?family=Carlito&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

</head>
<body>
    
    <div id="kopf" style="display: flex; align-items: center;">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">	   
	        <h1>ClubCash Portal</h1>
	</div>
    
    
    <div id="login-container">
       
       <p>Gebe deine Benutzerdaten von Vereinfleiger.de ein.</p> 
        
        <?php if (isset($error_message)) echo "<p style='color: red;'>$error_message</p>"; ?>
        
        <form method="POST" action="">
            <label for="username">Email:</label>
            <input type="text" name="username" id="username" required><br>

            <label for="password">Passwort:</label>
            <input type="password" name="password" id="password" required><br>

            <input type="submit" value="Anmelden">
        </form>
    </div>
</body>
</html>
