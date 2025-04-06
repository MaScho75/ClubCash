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
    
    <script src="config.js?v=<?php echo time(); ?>"></script>

</head>
<body class="portal">

    <!-- Preloader anzeigen -->
    <div class="preloader" id="preloader">
        <div class="spinner"></div>
    </div>

     <div id="kopf" style="display: flex; align-items: center;">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">	   
	        <h1>ClubCash Portal</h1>
	</div>
    

<nav class="navbar">
  <ul>
    <li>
      <a href="#">Mein Konto</a>
      <ul>
        <li><a href="" onclick="location.reload()">Programminfo</a></li>
        <li><a href="#" onclick="Meine_Käufe()">Meine Käufe</a></li>
        <li><a href="#" onclick="Käufe_Zusammenfassung()">Käufe Zusammenfassung</a></li>
        <li><a href="logout.php" >Abmelden</a></li>
      </ul>
    </li>   

    <li>
      <a href="#">Auswertung</a>
      <ul>
        <li><a href="#" class="disabled">Tagesabrechnung heute</a></li>
        <li><a href="#" class="disabled">Tagesabrechnung Datum</a></li>
        <li><a href="#" class="disabled">Zusammenfassung heute</a></li>
        <li><a href="#" class="disabled">Zusammenfassung Datum</a></li>    
      </ul>
    </li>

    <li>
      <a href="#">Administration</a>
      <ul>
        <li><a href="#" onclick="produktkatalog_aufrufen()">Produktkatalog</a></li>
        <li><a href="#" onclick="verkaufsliste()">Verkaufsliste</a></li>
        <li><a href="#" onclick="Mitgliederdaten_anzeigen()">Kundenliste</a></li>
        <li><a href="#" onclick="Mitgliedsdaten_ziehen()">Kundenliste Import VF</a></li>
        <li><a href="#" class="disabled">Gesamtabrechnung</a></li>
        <li><a href="#" class="disabled">Einzelabrechnung</a></li>
        <li><a href="#" class="disabled">Konten zurücksetzen</a></li>
        <li><a href="#" class="disabled">Einzelkonto zurücksetzen</a></li>
        <li><a href="#" class="disabled">Export an Vereinsflieger</a></li>
      </ul>
    </li>
 
    <li>
      <a href="#">Einstellungen</a>
      <ul>
        <li><a href="#" class="disabled">Zugriff Vereinsflieger</a></li>
        <li><a href="#" class="disabled">Farben</a></li>
        <li><a href="#" class="disabled" class="disabled">Porgrammeinstellungen</a></li>
        <li><a href="#" class="disabled">alle Daten löschen</a></li>
      </ul>
    </li>
    <li>
      <a href="#">Download</a>
      <ul>
        <li><a href="daten/produkte.csv" >Produktliste CSV</a></li>
        <li><a href="daten/kunden.json" >Kugendliste JSON</a></li>
        <li><a href="daten/verkaufsliste.csv" >Verkaufsliste CSV</a></li>
        <li><a href="#" onclick="backupliste()">Backups</a></li>
      </ul>
    </li>
  </ul>
</nav>
       
<div id="portal-inhalt">
    <p>Hallo <span id="userName"></span>, willkommen im Portal!</p>
    <?php include('info.html'); ?>
</div>


    <script>

        //Sanduhr
        window.onload = function() {
        // Preloader ausblenden, wenn die Seite vollständig geladen ist
        document.getElementById("preloader").style.display = "none";
        }
        
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
        console.log('Angemeldetes Mitglied:', angemeldetesMitglied);	


    function Mitgliederdaten_anzeigen() {
		
        fetch('daten/kunden.json')
            .then(response => response.json())
            .then(data => {
                const kunden = data;
                console.log('Kunden geladen:', kunden);
                
                let html = '<h2>Kundenliste</h2><table class="portal-table" style="max-width: none;">';
                html += `
                <tr>
                    <th>ID</th>
                    <th class="links">Vorname</th>
                    <th class="links">Nachname</th>
                    <th class="links">Email</th>
                    <th>Schlüssel</th>
                    <th>Kasse</th>
                    <th>CLD</th>
                    <th>Mitglied</th>
                    <th>Gast</th>                
                </tr>`;

                kunden.forEach(kunde => {
                    html += `<tr>
                        <td>${kunde.uid}</td>
                        <td class="links">${kunde.firstname}</td>
                        <td class="links">${kunde.lastname}</td>
                        <td class="links">${kunde.email}</td>
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
        portalInhalt.innerHTML = "<h2>Vereinsflieger Datenimport</h2><p>Bitte warten, die Mitgliederdaten werden aus Vereinsflieger abgerufen...</p>";
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

    function Meine_Käufe() {
        let summe = 0;
        let tabelle_html = "";
        tabelle_html = `
            <h2>Meine Käufe</h2>
            <p><i>vollständige Liste - nur ClubCash System</i></p>`;
        tabelle_html += `
        <table class="portal-table">
            <tr>
                <th>Datum</th>
                <th>Zeit</th>
                <th>Terminal</th> 
                <th class="links">Produkt</th>
                <th class="rechts">Preis</th>
            </tr>
        <tbody>`;

        verkäufe.forEach(verkauf => {
            if (verkauf.Kunde === angemeldetesMitglied.key2designation) {
                tabelle_html += `
                    <tr>
                        <td>${verkauf.Datum}</td>
                        <td>${verkauf.Zeit}</td>
                        <td>${verkauf.Terminal}</td>
                        <td class="links">${verkauf.Produkt}</td>
                        <td class="rechts">${verkauf.Preis} €</td>
                    </tr>`;
                    if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                        summe += parseFloat(verkauf.Preis);
                    }
            }
        }); 

        tabelle_html += `
            <tr>
                <td colspan="4" class="links"></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>
        </tbody></table>`;
        portalInhalt.innerHTML = tabelle_html;
    }

    function Käufe_Zusammenfassung() { 
        let summe = 0;
        let produktsumme = 0;
        let tabelle_html = "";
        tabelle_html = `
            <h2>Meine Käufe - Zusammenfassung</h2>
            <p><i>* aktueller Preis - kann sich geändert haben</i></p>`;
        tabelle_html += `
        <table class="portal-table">
            <tr>
                <th>Anzahl</th>
                <th class="links">Produkt</th>
                <th class="rechts">Einzelpreis*</th> 
                <th class="rechts">Gesamtpreis</th>
            </tr>
        <tbody>`;

        console.log('Produkte:', produkte); // Debug-Ausgabe der Produkte
        console.log('Verkäufe:', verkäufe); // Debug-Ausgabe der Verkäufe
        
        produkte.forEach(produkt => {
            produktsumme = 0;
            produktanzahl = 0;
            verkäufe.forEach(verkauf => {
                if (verkauf.Kunde === angemeldetesMitglied.key2designation && verkauf.EAN === produkt.EAN) {
                    if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                        produktsumme += parseFloat(verkauf.Preis);
                    }
                    produktanzahl++;
                }
            });
            
            if (produktanzahl === 0) return; // Wenn keine Verkäufe für dieses Produkt, überspringen
            
            summe += produktsumme;

            tabelle_html += `
                <tr>
                    <td>${produktanzahl}</td>
                    <td class="links">${produkt.Bezeichnung}</td>
                    <td class="rechts">${produkt.Preis} €</td>
                    <td class="rechts">${produktsumme.toFixed(2)} €</td>
    
                </tr>`;
        });
        tabelle_html += `
            <tr>
                <td colspan="3" class="links"></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>
            </tbody>
            </table>`;
        portalInhalt.innerHTML = tabelle_html;
    }

    function backupliste() {
        fetch('get-backup-files.php')
        .then(response => response.text())
        .then(data => {
            portalInhalt.innerHTML = data;
        })
        .catch(error => console.error('Fehler beim Laden der Dateien:', error));
    }

    </script>




</body>
</html>
