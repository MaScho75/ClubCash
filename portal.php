<?php
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

// Mitgliederdaten laden
$jsonKundenDatei = file_get_contents("daten/kunden.json");
$jsonKundenDaten = json_decode($jsonKundenDatei, true); // true gibt ein assoziatives Array zurück

// csv produkte laden
$csvDatei = "daten/produkte.csv"; 
$produkte = [];

if (($handle = fopen($csvDatei, "r")) !== FALSE) {
    $header = fgetcsv($handle, 1000, ";"); // Erste Zeile als Header lesen (Spaltennamen)

    while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (count($row) == count($header)) { // Nur Zeilen mit vollständigen Werten verarbeiten
            $produkte[] = array_combine($header, $row); // Header mit Werten kombinieren
        }
    }
    fclose($handle);
}

// csv verkaufsliste laden
$csvDatei2 = "daten/verkaufsliste.csv"; 
$verkäufe = [];

if (($handle = fopen($csvDatei2, "r")) !== FALSE) {
    $header = fgetcsv($handle, 1000, ";"); // Erste Zeile als Header lesen (Spaltennamen)

    while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (count($row) == count($header)) { // Nur Zeilen mit vollständigen Werten verarbeiten
            $verkäufe[] = array_combine($header, $row); // Header mit Werten kombinieren
        }
    }
    fclose($handle);
}

//print_r($produkte); // Debug-Ausgabe der Produkte
//print_r($verkäufe); // Debug-Ausgabe der Verkäufe
//print_r($jsonKundenDaten); // Debug-Ausgabe der Mitgliederdaten

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Zeichensatz auf UTF-8 setzen -->
    <meta charset="UTF-8">

    <!-- Skalierbarkeit für mobile Geräte sicherstellen -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cafè Lüsse Portal</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
	
	<link href="https://fonts.googleapis.com/css2?family=Carlito&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="admin/config.js?v=<?php echo time(); ?>"></script>



</head>
<body class="portal">
     <div id="kopf" style="display: flex; align-items: center;">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">	   
	        <h1>ClubCash Portal</h1>
	</div>
    
    <div id="portal-container">
        <nav class="top-menu">
            <ul>
            <li class="dropdown">
                <a href="#" class="dropbtn">Mein Konto</a>
                <div class="dropdown-content">
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Meine Käufe</button>
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Meine Käufe Zusammenfassung</button>
                <a href="logout.php"><button>Abmelden</button></a>
                </div>
            </li>

            <li class="dropdown">
                <a href="#" class="dropbtn">Produkte & Verkäufe</a>
                <div class="dropdown-content">
                <button onclick="produktkatalog_aufrufen()">Produktkatalog</button>
                <button onclick="verkaufsliste()">Verkaufsliste</button>
                <button onclick="Mitgliederdaten_anzeigen()">Kundenliste</button>
                </div>
            </li>

            <li class="dropdown">
                <a href="#" class="dropbtn">Administration</a>
                <div class="dropdown-content">
                <button onclick="Mitgliedsdaten_ziehen()">Kunden import aus Vereinsflieger</button>
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Konfiguration anzeigen</button>
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Abrechnung an alle senden</button>
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Einzelabrechnung senden</button>
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Konten zurücksetzen</button>
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Export an Vereinsflieger</button>
                </div>
            </li>

            <li class="dropdown">
                <a href="#" class="dropbtn">Abrechnungen</a>
                <div class="dropdown-content">
                <a href="abrechnung"><button>Tagesabrechnung heute</button></a>
                <button disabled title="Diese Funktion ist derzeit nicht verfügbar.">Tagesabrechnung Datum</button>
                </div>
            </li>

            <li class="dropdown">
                <a href="#" class="dropbtn">Downloads</a>
                <div class="dropdown-content">
                <a href="download.php?file=produkte.csv"><button>Produkte (CSV)</button></a>
                <button disabled title="Direkter Zugriff auf Kunden (JSON) ist aus Sicherheitsgründen deaktiviert.">Kunden (JSON)</button>
                <a href="download.php?file=verkaufsliste.csv"><button>Verkaufsliste (CSV)</button></a>
                </div>
            </li>
            </ul>
        </nav>

        <div id="portal-inhalt">
            <p>Hallo <span id="userName"></span>,<br>willkommen im Portal!</p>
        </div>
    </div>

    <script>
        // PHP-Variablen in JavaScript-Variablen umwandeln
        const kunden = <?php echo json_encode($jsonKundenDaten); ?>;
        const produkte = <?php echo json_encode($produkte); ?>;
        const verkäufe = <?php echo json_encode($verkäufe); ?>;

        const portalInhalt = document.getElementById('portal-inhalt');
        const portalMenu = document.getElementById('portal-menu');

        // Finde das angemeldete Mitglied anhand der Email-Adresse (case-insensitive)
        const angemeldetesMitglied = kunden.find(kunde => 
            kunde.email.toLowerCase() === '<?php echo strtolower($_SESSION['username']); ?>');
        document.getElementById('userName').textContent = `${angemeldetesMitglied.firstname} ${angemeldetesMitglied.lastname}`;

    function Mitgliederdaten_anzeigen() {
		
        fetch('daten/kunden.json')
            .then(response => response.json())
            .then(data => {
                const kunden = data;
                console.log('Kunden geladen:', kunden);
                
                let html = '<h2>Kundenliste</h2><table border="1">';
                html += `
                <tr>
                    <th>ID</th>
                    <th>Vorname</th>
                    <th>Nachname</th>
                    <th>Email</th>
                    <th>Schlüssel</th>
                    <th>Kasse</th>
                    <th>Cafe Lüsse Dienst</th>
                    <th>Mitglied</th>
                    <th>Gast</th>                
                </tr>`;

                kunden.forEach(kunde => {
                    html += `<tr>
                        <td>${kunde.uid}</td>
                        <td>${kunde.firstname}</td>
                        <td>${kunde.lastname}</td>
                        <td>${kunde.email}</td>
                        <td>${kunde.key2designation}</td>
                        <td>${kunde.cc_admin}</td>
                        <td>${kunde.cc_seller}</td>
                        <td>${kunde.cc_member}</td>
                        <td>${kunde.cc_guest}</td>
                    </tr>`;
                });

                html += '</table>';
                portalInhalt.innerHTML = html;
            })
            .catch(error => {
                console.error('Fehler beim Laden der Kunden:', error);
                portalInhalt.innerHTML = '<p>Fehler beim Laden der Mitgliederdaten</p>';
            });

    }	

    function Mitgliedsdaten_ziehen() {
        portalInhalt.innerHTML = "<p>Bitte warten, die Mitgliederdaten werden aus Vereinsflieger abgerufen...</p>";
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "admin/pull_Mitgliedsdaten_Vereinsflieger.php", true); 
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                portalInhalt.innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }


    function produktkatalog_aufrufen() {
        portalInhalt.innerHTML = "<h2>Produktkatalog</h2><iframe src='admin/produkte.html?v=" + Date.now() + "' style='width: 100%; height: 700px'></iframe>";
    }

    function verkaufsliste() {
        portalInhalt.innerHTML ="<h2>Verkaufsliste</h2><iframe src='admin/verkaeufe.html?v=" + Date.now() + "' style='width: 100%; height: 700px'></iframe>";
    }    

    function toggleMenu() {
            document.querySelector(".menu").classList.toggle("active");
    }

    </script>




</body>
</html>
