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

//Erfoderlichekeiten, damit PHPMailer funktioniert
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;


// Mitgliederdaten laden
    clearstatcache(true, "daten/kunden.json"); // Clear file cache for this specific file
    $jsonKundenDatei = file_get_contents("daten/kunden.json");
    $jsonKundenDaten = json_decode($jsonKundenDatei, true); // true gibt ein assoziatives Array zurück

// Externe Kunden laden
    clearstatcache(true, "daten/externe.json"); // Clear file cache for this specific file
    $jsonExterneDatei = file_get_contents("daten/externe.json");
    $jsonExterneDaten = json_decode($jsonExterneDatei, true); // true gibt ein assoziatives Array zurück

// Produkte laden
    clearstatcache(true, "daten/produkte.json"); // Clear file cache for this specific file
    $jsonProdukteDatei = file_get_contents("daten/produkte.json");
    $jsonProdukteDaten = json_decode($jsonProdukteDatei, true); // true gibt ein assoziatives Array zurück

// wareneingang laden
    clearstatcache(true, "daten/wareneingang.json"); // Clear file cache for this specific file
    $jsonWareneingangDatei = file_get_contents("daten/wareneingang.json");
    $jsonWareneingangDaten = json_decode($jsonWareneingangDatei, true); // true gibt ein assoziatives Array zurück

// Configurationsdatei einbinden
    clearstatcache(true, "daten/config.json"); // Clear file cache for this specific file
    $jsonConfigDatei = file_get_contents("daten/config.json");
    $jsonConfigDaten = json_decode($jsonConfigDatei, true); // true gibt ein assoziatives Array zurück

// csv umsatz laden
    clearstatcache(true, "daten/umsatz.csv"); // Clear file cache for this specific file
    $csvDatei2 = "daten/umsatz.csv"; 
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

// Ermittle die aktuelle zum Download verfügbare Version, die auf GitHub hinterlegt ist
$owner = 'MaScho75';
$repo = 'ClubCash';
$url = "https://api.github.com/repos/$owner/$repo/releases/latest";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Script');

$response = curl_exec($ch);
curl_close($ch);

if ($response !== false) {
    $release = json_decode($response, true);
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Zeichensatz auf UTF-8 setzen -->
    <meta charset="UTF-8">

    <!-- Skalierbarkeit für mobile Geräte sicherstellen -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ClubCash Portal</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
	
	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	
</head>
<body class="portal">

    <!-- Preloader anzeigen -->
    <div class="preloader" id="preloader">
        <div class="spinner"></div>
    </div>

     <div id="kopf" style="display: flex; align-items: center;">
                <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">
                <div>
                    <div style="display: flex; align-items: center;">
                        <h1>ClubCash Portal</h1>
                        <p>&nbsp;<b><span id="Version">x.x.x</span></b></p>
                    </div>
                    <p id="vereinsnamen">Vereinsnamen</p>
                </div>
            </div>

<nav class="navbar">
  <div class="hamburger" id="hamburger">
    <span></span>
    <span></span>
    <span></span>
  </div>
  <ul id="navMenu">
    <li>
      <a href="#" id="MenuMeinKonto" style="display: none;">Mein Konto</a>
      <ul>
        <li><a href="" onclick="location.reload()">Programminfo</a></li>
        <li><a href="#" onclick="Kundenübersicht(angemeldetesMitglied.uid)">Übersicht</a></li>
        <li><a href="#" onclick="OnlineBuchung(angemeldetesMitglied.uid)">Buchung</a></li>
        <li><a href="#" onclick="TankstellenQRCode()">Tankstelle</a></li>
        <li><a href="logout.php">Abmelden</a></li>
      </ul>
    </li>   

    <li>
      <a href="#" id="MenuAuswertung" style="display: none;">Auswertung</a>
      <ul>
        <li><a href="#" onclick="Tagesumsätze()">Tagesumsätze</a></li>
        <li><a href="#" onclick="Tageszusammenfassung()">Tageszusammenfassung</a></li>
        <li><a href="#" onclick="Kundentagesübersicht()">Mitglieder-Tagesumsätze</a></li>     
        <li><a href="#" onclick="Preisliste_drucken()">Preisliste</a></li>
        <li><a href="#" onclick="Preisliste_strichcode()">Strichcodeliste</a></li>
        <li><a href="#" onclick="Preisliste_Eiskarte()">Eiskarte</a></li></ul>
    </li>

    <li>
      <a href="#" id="MenuAdministrator" style="display: none;">Administration</a>
      <ul>
        <li><a href="#" onclick="Mitgliederdaten_anzeigen()">Mitgliederliste</a></li>
        <li><a href="#" onclick="ExterneKunden()">Externe</a></li>
        <li><a href="#" onclick="Produkte_editieren()">Produkte</a></li>
        <li><a href="#" onclick="Wareneingang()">Wareneingang</a></li>
        <li><a href="#" onclick="Umsätze()">Umsätze</a></li>
        <li><a href="#" onclick="Abrechnung()">Abrechnung</a></li>
      </ul>
    </li>
 
    <li>
      <a href="#" id="MenuEinstellungen" style="display: none;">Einstellungen</a>
      <ul>
        <li><a href="#" onclick="Programmeinstellungen()">Programmeinstellungen</a></li>
        <li><a href="#" onclick="Update()">Update</a></li>
        <li><a href="#" onclick="Systembackup()">Systembackup</a></li>
        <li><a href="#" onclick="Sicherheitscheck()">Sicherheitscheck</a></li>
        <li><a href="#" onclick="Farben()">Farben</a></li>
      </ul>
    </li>
    <li>
      <a href="#" id="MenuDownload" style="display: none;">Download</a>
      <ul>
        <li><a href="#" onclick="downloadFile('daten/produkte.json')">Produktliste JSON</a></li>
        <li><a href="#" onclick="downloadFile('daten/kunden.json')">Kundenliste JSON</a></li>
        <li><a href="#" onclick="downloadFile('daten/externe.json')">Externe Kunden JSON</a></li>
        <li><a href="#" onclick="downloadFile('daten/umsatz.csv')">Umsatz CSV</a></li>
        <li><a href="#" onclick="Mitgliederdaten()">Mitgliederdaten</a></li>
        <li><a href="#" onclick="backupliste()">Backups</a></li>
      </ul>
    </li>
  </ul>
</nav>

<div id="portalmenu2">
    <h2>Hallo <span id="userName"></span>, willkommen im Portal!</h2>
</div>

<div id="portal-inhalt">
    <?php include('info.html'); ?>
</div>


<script>

// ============================================================================
// INITIALISIERUNG & EVENT LISTENER
// ============================================================================

    // Hamburger Menu Toggle - Öffnet/Schließt das mobile Navigationsmenü
    document.getElementById('hamburger').addEventListener('click', function() {
        document.getElementById('navMenu').classList.toggle('active');
        this.classList.toggle('active');
    });

    // Mobile submenu toggle
    document.querySelectorAll('.navbar > ul > li > a').forEach(item => {
        item.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const submenu = this.nextElementSibling;
                if (submenu && submenu.tagName === 'UL') {
                    submenu.classList.toggle('active');
                }
            }
        });
    });

    // Schließe Menü wenn außerhalb geklickt wird
    document.addEventListener('click', function(event) {
        const navbar = document.querySelector('.navbar');
        const hamburger = document.getElementById('hamburger');
        if (!navbar.contains(event.target)) {
            document.getElementById('navMenu').classList.remove('active');
            hamburger.classList.remove('active');
        }
    });

    let portalmenu2 = document.getElementById('portalmenu2');

    // Datum mitteleuropäisch formatiert
        let heute = new Date();

    //Wochenbeginn
        let wochenbeginn = new Date(heute);
        let day = heute.getDay();
        day = (day === 0) ? 7 : day; // Sonntag (0) wird zu 7
        wochenbeginn.setDate(heute.getDate() - (day - 1)); // auf Montag zurückrechnen
        wochenbeginn.setHours(2, 0, 0, 0); // 2 Uhr früh, Minuten/Sekunden/Millisekunden auf 0 setzen

    //Monatsbeginn
        let monatsbeginn = new Date(heute);
        monatsbeginn.setDate(1); // Erster Tag des Monats
        monatsbeginn.setHours(monatsbeginn.getHours() + 2); // 2 Stunden addieren

    //Jahresbeginn
        let jahresbeginn = new Date(heute);
        jahresbeginn.setDate(1); // Erster Tag des Jahres
        jahresbeginn.setMonth(0); // Januar
        jahresbeginn.setHours(jahresbeginn.getHours() + 2); // 2 Stunden addieren

    //Sanduhr
        window.onload = function() {
        // Preloader ausblenden, wenn die Seite vollständig geladen ist
        document.getElementById("preloader").style.display = "none";
        }
    
    // PHP-Variablen in JavaScript-Variablen umwandeln
        let kunden = <?php echo json_encode($jsonKundenDaten); ?>;
        let externe = <?php echo json_encode($jsonExterneDaten); ?>;
        let produkte = <?php echo json_encode($jsonProdukteDaten); ?>;
        let verkäufe = <?php echo json_encode($verkäufe); ?>;
        let wareneingang = <?php echo json_encode($jsonWareneingangDaten); ?>;
        let customer_login = <?php echo json_encode($_SESSION['customer_login']); ?>;
        let config = <?php echo json_encode($jsonConfigDaten); ?>;
        const release = <?php echo json_encode($release); ?>;

    // Definierte Spaltenreihenfolge für Produkttabellen
        const produktKeys = ['EAN', 'Bezeichnung', 'Kategorie', 'Preis', 'MwSt', 'Bestand', 'Min', 'Zählerstand', 'Menge', 'Sortierung', 'Bemerkung'];
    
    // Definierte Spaltenreihenfolge für Wareneingang
        const wareneingangKeys = ['Eingang', 'EAN', 'Menge'];

    // Mitglieder und externe Kunden zusammenführen    
        let käufer = MitgliederExterneZusammenführen();
    
    //aktuelle Kontostände der Kunden berechnen und in die Kundenliste einfügen
        let kundenkontostand = Kundenkontostand(verkäufe);
        käufer.forEach(kunde => {
            const kontostand = kundenkontostand.find(k => k.Kundennummer === kunde.uid);
            kunde.Kontostand = parseFloat(kontostand?.Summe || 0); // Add Kontostand to kunde object
            kunde.Kategorien = kontostand?.Kategorien || []; // Add full transaction data as sub-array
        });
        
    // Version anzeigen
        document.getElementById('Version').textContent = config.Version;

    // Vereinsnamen anzeigen
        document.getElementById('vereinsnamen').textContent = config.Vereinsname;

    // Bereinige die Schlüssel von BOM und unsichtbaren Zeichen
        wareneingang = wareneingang.map(item => {
            const cleanItem = {};
            Object.entries(item).forEach(([key, value]) => {
                // Entferne alle unsichtbaren Zeichen am Anfang und Ende
                const cleanKey = key.replace(/^[\uFEFF\u200B\u0000-\u0020]+|[\u0000-\u0020]+$/g, '');
                cleanItem[cleanKey] = value;
            });
            return cleanItem;
        });

    //Elemente für die Anzeige im Portal    
        const portalInhalt = document.getElementById('portal-inhalt');
        const portalMenu = document.getElementById('portal-menu');

    // Finde das angemeldete Mitglied anhand der Email-Adresse (case-insensitive)
        let angemeldetesMitglied = käufer.find(kunde => 
            kunde.email.toLowerCase() === '<?php echo strtolower($_SESSION['username']); ?>');

        // Wenn kein Mitglied gefunden wurde, setzt folgende Werte für angemeldetesMitglied
        if (!angemeldetesMitglied) {
            installansicht = true; // Setze installansicht auf true, wenn kein Mitglied gefunden wurde
            //console.error("Angemeldetes Mitglied nicht gefunden.");
            // Setze Standardwerte oder handle den Fehler entsprechend
            angemeldetesMitglied = {
                firstname: 'neuer',
                lastname: 'Administrator',
                cc_seller: false,
                cc_admin: false,
                cc_member: false,
                cc_guest: false
            };
        }
        
        document.getElementById('userName').textContent = angemeldetesMitglied.firstname + " " + angemeldetesMitglied.lastname;
    
    //Menu gemäß Rollen ein- und ausblenden

        if (config.demo === "true") {
            console.log("Demo-Modus ist aktiv. Alle Menüpunkte werden angezeigt.");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'block';
            document.getElementById('MenuEinstellungen').style.display = 'block';
            document.getElementById('MenuDownload').style.display = 'block';
        }
        else if (customer_login === true && angemeldetesMitglied.cc_seller === true) {
            console.log("Benutzer ist Verkäufer. Login mit Schlüssel");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (customer_login === true ) {
            console.log("Benutzer ist Kunde. Login mit Schlüssel");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'none';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (config.zugriffseinschränkung === "false") {
            console.log("Zugriffseinschränkung ist deaktiviert. Alle Menüpunkte werden angezeigt.");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'block';
            document.getElementById('MenuEinstellungen').style.display = 'block';
            document.getElementById('MenuDownload').style.display = 'block';
        } else if (angemeldetesMitglied.cc_admin === true) {
            console.log("Zugriffseinschränkung ist aktiv. Benutzer ist Administrator.");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'block';
            document.getElementById('MenuEinstellungen').style.display = 'block';
            document.getElementById('MenuDownload').style.display = 'block';
        } else if (angemeldetesMitglied.cc_seller === true) {
            console.log("Zugriffseinschränkung ist aktiv. Benutzer ist Verkäufer.");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (angemeldetesMitglied.cc_member === true) {
            console.log("Zugriffseinschränkung ist aktiv. Benutzer ist Mitglied.");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'none';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (angemeldetesMitglied.cc_guest === true) {
            console.log("Zugriffseinschränkung ist aktiv. Benutzer ist Gast.");
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'none';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        }

        // Sollte gerade die Seite nach der Löschung eines Backupfiles aufgerufen werden, dann öffne die Backupliste
           const urlParams = new URLSearchParams(window.location.search);
           const action = urlParams.get('action');

        // Prüfen, ob 'action' gesetzt ist und den Wert 'backupliste' hat
           if (action === 'backupliste') {
                backupliste(); // JavaScript-Funktion aufrufen
                // Danach 'action' aus der URL entfernen, ohne neu zu laden:
                urlParams.delete('action');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', newUrl);
           }

// ============================================================================
// BUCHUNGEN & VERKÄUFE
// ============================================================================

    /**
     * Löscht einen Verkauf aus der Umsatzdatei
     * @param {number} index - Index des zu löschenden Verkaufs im Array
     */
    function deleteVerkauf(index) {
        if (confirm(`Möchtest du den ausgewählten Verkauf wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!`)) {
            // Verkauf aus dem Array entfernen
            verkäufe.splice(index, 1);

            //Bildschirm leeren
            document.getElementById("preloader").style.display = "block"; // Preloader anzeigen
            portalmenu2.innerHTML = '<h2>Datenaktualisierung</h2>';
            portalInhalt.innerHTML = '<p>Bitte Warten! Der Datenbestand wird aktualisiert. Es können in dieser Zeit keine Verkäufe getätigt werden!<br>Nach Abschluss der Aktualierung wird die Seite neu gelanden.</p>';

            // Array zur Übertragung vorbereiten
            const csvData = {
                data: verkäufe,
                filename: "daten/umsatz.csv"
            };

            // Daten an den Server senden, um die CSV-Datei zu überschreiben
            fetch('csv-schreiben.php' , {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(csvData)
            })
            .then(response => response.json())
            .then(result => { 
                    alert(`Der ausgewählte Verkauf wurde erfolgreich gelöscht.`);
            })
            .catch(error => {
                alert('Fehler beim Löschen des Verkaufs: ' + error);
            })
            .finally(() => location.reload()); // seite aktualisieren
        }
    }       

    /**
     * Zeigt Produktauswahl für manuelle Buchung auf ein Kundenkonto
     * @param {string} KdNr - Kundennummer des ausgewählten Kunden
     */
    function OnlineBuchung(KdNr) {
        let AusgewählterKunde = käufer.find(kunde => kunde.uid === KdNr);
        let AusgewählterKundenname = AusgewählterKunde ? `${AusgewählterKunde.firstname} ${AusgewählterKunde.lastname}` : 'Unbekannt';
        let Kategorie;
        let menu2 = `<h2 style='display: inline;'>Buchung</h2><p>Durch die Auswahl eines der folgenden Produktes wird es auf das Konto von <b>${AusgewählterKundenname}</b> geschrieben.</p>`;
        let html = "<button onclick='OnlineBuchung_ManuelleBuchung()' style='width: 400px; height: 80px; margin: 10px;' class='grosserBt'>manuelle Buchung</button>";
  
        sortedProdukte = [...produkte].sort((a, b) => {
            if (a.Kategorie === b.Kategorie) {
                // Numerischer Vergleich der Sortierungswerte
                return parseInt(a.Sortierung) - parseInt(b.Sortierung);
            }
            return a.Kategorie.localeCompare(b.Kategorie);
        });

        sortedProdukte.forEach(produkt => {
            if (produkt.EAN === "9990000000000") return; // Skip "manuelle Buchung")
            if (Kategorie !== produkt.Kategorie) {
                Kategorie = produkt.Kategorie;
                html += `
                    <h3 style="margin-top: 30px; margin-bottom: 10px;">${Kategorie}</h3>
                `;
            }
            html += `
                <button style="width: 400px; height: 80px; margin: 10px;" class="grosserBt" onclick="OnlineBuchung_Produkt('${produkt.EAN}', '${produkt.Bezeichnung}', '${produkt.Kategorie}', '${produkt.Preis}', '${produkt.MwSt}', '${KdNr}', '${AusgewählterKundenname}')">
                    ${produkt.Bezeichnung} <br> ${parseFloat(produkt.Preis).toFixed(2)} €
                </button>
            `;
        });
        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;
    }

    /**
     * Bucht ein ausgewähltes Produkt auf das Kundenkonto
     * @param {string} EAN - EAN-Code des Produkts
     * @param {string} Bezeichnung - Produktbezeichnung
     * @param {string} Kategorie - Produktkategorie
     * @param {number} Preis - Produktpreis
     * @param {number} MwSt - Mehrwertsteuersatz
     * @param {string} KdNr - Kundennummer
     * @param {string} AusgewählterKundenname - Name des Kunden für Bestätigung
     */
    function OnlineBuchung_Produkt(EAN, Bezeichnung, Kategorie, Preis, MwSt, KdNr, AusgewählterKundenname) {
        
        Preis = parseFloat(Preis).toFixed(2); // Sicherstellen, dass Preis eine Zahl mit 2 Dezimalstellen ist

        let buchungsDaten = {
            Datum: heute.toISOString().split('T')[0],
            Zeit: heute.toTimeString().split(':').slice(0,2).join(':'),
            Terminal: 'M',
            Schlüssel: angemeldetesMitglied.schlüssel,
            Kundennummer: KdNr,
            EAN: EAN,
            Produkt: Bezeichnung,
            Kategorie: Kategorie,
            Preis: Preis,
            MwSt: MwSt
        };

        if (confirm(`Möchtest du das Produkt "${Bezeichnung}" für ${Preis} € auf das Konto von ${AusgewählterKundenname} gebucht wird?`)) {
            document.getElementById("preloader").style.display = "block"; // Preloader anzeigen
            fetch('./kasse/umsatz-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify([buchungsDaten])
            })
            .then(response => response.json())
            .then(result => { 
                    alert(`Das Produkt "${Bezeichnung}" wurde erfolgreich auf das Konto von ${AusgewählterKundenname} gebucht.`);
                    // Daten anfügen
                    verkäufe.push(buchungsDaten);
                    // Kontostand aktualisieren
                    angemeldetesMitglied.Kontostand += parseFloat(Preis);
                    // Zurück zur Buchungsseite
                    document.getElementById("preloader").style.display = "none"; // Preloader ausblenden
                    OnlineBuchung(KdNr);
            })
            .catch(error => {
                alert('Fehler bei der Buchung: ' + error);
                document.getElementById("preloader").style.display = "none"; // Preloader ausblenden 
            });
        }
    }


// ============================================================================
// KUNDENVERWALTUNG
// ============================================================================

    /**
     * Verwaltet externe Kunden (ohne Vereinsflieger-Zugang)
     * Ermöglicht Hinzufügen, Bearbeiten und Löschen von externen Kunden
     */
    function ExterneKunden() {
        let data = externe ? [...externe] : []; // Copy of original data
        let originalData = externe ? [...externe] : [];
        let editedRows = new Set();
        let newRows = new Set();
        let deletedRows = new Set();
        let sortColumn = '';
        let sortAscending = true;

        let menu2 = "<h2 style='display: inline;'>Externe Kunden</h2>";
        menu2 += `
            <button id="addButton" class="kleinerBt">hinzufügen</button>
            <button id="saveButton" class="kleinerBt">speichern</button>
            <button onclick="location.reload();" class="kleinerBt">abbruch</button>
        `;

        let html = "<p>Die folgenden Kunden haben keinen Vereinsfliegerzugang:</p>";
        html += `
            <table id="dataTable" class="portal-table">
                <thead>
                    <tr>
                        <th class="sortable" data-field="firstname">Vorname</th>
                        <th class="sortable" data-field="lastname">Nachname</th>
                        <th class="sortable" data-field="email">Email</th>
                        <th>Schlüssel</th>
                        <th>BezugsID</th>
                        <th>Beziehung</th>
                        <th>Aktionen</th>
                        <th>Kontostand</th>
                        <th>i</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                </tbody>
            </table>`;

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

        // Add sorting functionality
        document.querySelectorAll('.sortable').forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const field = th.dataset.field;
                if (sortColumn === field) {
                    sortAscending = !sortAscending;
                } else {
                    sortColumn = field;
                    sortAscending = true;
                }
                
                data.sort((a, b) => {
                    let valA = (a[field] || '').toLowerCase();
                    let valB = (b[field] || '').toLowerCase();
                    return sortAscending ? 
                        valA.localeCompare(valB) : 
                        valB.localeCompare(valA);
                });
                
                // Update sort indicators
                document.querySelectorAll('.sortable').forEach(header => {
                    header.textContent = header.textContent.replace(/[▲▼]/, '');
                });
                th.textContent += sortAscending ? ' ▲' : ' ▼';
                
                renderTable();
            });
        });

        function generateUniqueKey() {
            let baseNum = 9900000000;
            let existingKeys = new Set([
                ...verkäufe.map(v => v.Schlüssel),
                ...externe.map(e => e.schlüssel), 
                ...kunden.map(k => k.schlüssel),
                ...data.map(d => d.schlüssel) // Include current data
            ].filter(Boolean));
            
            while (existingKeys.has(baseNum.toString())) {
                baseNum++;
            }
            return baseNum.toString();
        }

        function validateData() {
            // Check for empty required fields
            const emptyFields = data.some((row, index) => {
                if (deletedRows.has(index)) return false;
                return !row.firstname || !row.lastname || !row.email;
            });
            
            if (emptyFields) {
                alert('Vorname, Nachname und Email müssen für alle Datensätze ausgefüllt sein!');
                return false;
            }

            // Check for duplicate keys in new rows
            const keys = new Set();
            let hasDuplicates = false;
            data.forEach((row, index) => {
                if (!deletedRows.has(index)) {
                    if (keys.has(row.schlüssel)) {
                        hasDuplicates = true;
                    }
                    keys.add(row.schlüssel);
                }
            });

            if (hasDuplicates) {
                alert('Warnung: Es gibt doppelte Schlüssel in den Datensätzen!');
                return false;
            }

            return true;
        }

        function renderTable() {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';

            data.forEach((externer, index) => {
            const mitglied = kunden.find(m => m.uid === externer.bezuguid);
            const bezugname = mitglied ? `${mitglied.firstname} ${mitglied.lastname}` : 'kein Mitglied';
            
            const tr = document.createElement('tr');
            tr.classList.toggle('edited', editedRows.has(index));
            tr.classList.toggle('new', newRows.has(index));
            tr.classList.toggle('deleted', deletedRows.has(index));

            // Durchstreichen-Style für gelöschte Zeilen
            const tdStyle = deletedRows.has(index) ? 'text-decoration: line-through;' : '';

            console.log("Externer Kunde:", externer);    

            tr.innerHTML = `
                <td contenteditable="${!deletedRows.has(index)}" class="${!externer.firstname ? 'error' : ''}" style="${tdStyle}">${externer.firstname || ''}</td>
                <td contenteditable="${!deletedRows.has(index)}" class="${!externer.lastname ? 'error' : ''}" style="${tdStyle}">${externer.lastname || ''}</td>
                <td contenteditable="${!deletedRows.has(index)}" class="${!externer.email ? 'error' : ''}" style="${tdStyle}">${externer.email || ''}</td>
                <td contenteditable="${!deletedRows.has(index)}" style="${tdStyle}">${externer.schlüssel || ''}</td>
                <td contenteditable="${!deletedRows.has(index)}" style="${externer.bezuguid && !kunden.find(m => m.uid === externer.bezuguid) ? 'background-color: var(--warning-color);' : ''} ${tdStyle}">${externer.bezuguid || ''}</td>
                <td style="${tdStyle}">${bezugname}</td>
                <td>
                    <a href="#" class="icon" onclick="return false;">${deletedRows.has(index) ? '🔄' : '🗑️'}</a>
                         ${(editedRows.has(index) || deletedRows.has(index) || newRows.has(index)) ? 
                    '<a href="#" class="icon" onclick="return false;">↩️</a>' : ''}
                </td>
                <td style="${tdStyle}">${externer.Kontostand ? externer.Kontostand.toFixed(2) + ' €' : '0,00 €'}</td>
                <td style="${tdStyle}"><a href="#" class="icon" onclick="Kundenübersicht('${externer.schlüssel}')">ℹ️</a></td>
            `;
                
            // Add event listeners
            tr.querySelectorAll('td[contenteditable="true"]').forEach(td => {
                td.addEventListener('blur', () => {
                const field = ['firstname', 'lastname', 'email', 'schlüssel', 'bezuguid'][
                    Array.from(td.parentElement.children).indexOf(td)
                ];
                if (data[index][field] !== td.textContent.trim()) {
                    data[index][field] = td.textContent.trim();
                    if (!newRows.has(index)) {
                    editedRows.add(index);
                    }
                    renderTable();
                }
                });
            });

            // Delete/Restore button
            tr.querySelector('a:first-child').addEventListener('click', () => {
                if (deletedRows.has(index)) {
                deletedRows.delete(index);
                } else {
                deletedRows.add(index);
                }
                renderTable();
            });

            // Undo button
            const undoButton = tr.querySelector('a:nth-child(2)');
            if (undoButton) {
                undoButton.addEventListener('click', () => {
                if (newRows.has(index)) {
                    data.splice(index, 1);
                    newRows.delete(index);
                } else {
                    data[index] = {...originalData[index]};
                    editedRows.delete(index);
                    deletedRows.delete(index);
                }
                renderTable();
                });
            }

            tbody.appendChild(tr);
            });
        }

        // Initial render
        renderTable();

        // Add new row
        document.getElementById('addButton').addEventListener('click', () => {
            const newIndex = data.length;
            const newRow = {
                firstname: '',
                lastname: '',
                email: '',
                schlüssel: generateUniqueKey(),
                bezuguid: ''
            };
            data.push(newRow);
            originalData.push({...newRow});
            newRows.add(newIndex);
            renderTable();
        });

        // Save changes
        document.getElementById('saveButton').addEventListener('click', () => {
            if (!validateData()) {
                return;
            }

            const savedData = data.filter((_, index) => !deletedRows.has(index));

            fetch('json-schreiben.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    data: savedData,
                    filename: 'daten/externe.json'
                })
            })
            .then(response => response.text())
            .then(result => {
                alert('Externe Kunden erfolgreich gespeichert');
                externe = savedData;
                location.reload();
            })
            .catch(error => {
                alert('Fehler beim Speichern: ' + error);
            });
        });

        // Add CSS for error highlighting
        const style = document.createElement('style');
        style.textContent = `
            .error {
                background-color: #ffcccc;
            }
        `;
        document.head.appendChild(style);
    }

// ============================================================================
// PREISLISTEN & DRUCKFUNKTIONEN
// ============================================================================

    /**
     * Erstellt druckbare Eiskarte mit allen Eisprodukten
     * Öffnet neues Fenster mit formatierten Preisen
     */
    function Preisliste_Eiskarte() {
      
        // Neues Fenster öffnen
        let printWindow = window.open('', '_blank', 'width=800,height=600');

        // Produkte filtern und sortieren
        const sortedProdukte = [...produkte]
            .filter(p => p.Kategorie === 'Eis')
            .sort((a, b) => parseInt(a.Sortierung) - parseInt(b.Sortierung));

        // HTML zusammenbauen
        let html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Eiskarte</title>
	            <link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
	
	            <link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">
        
            </head>
            <body>
                <div style="text-align: center;">
                    <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; margin: 30px;">
                    <h1>Eiskarte</h1>
                    <p>Stand: ${heute.toLocaleDateString('de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' })}</p>
                </div>
                <div class="preisliste-print">
                    <table class="preisliste-table">
                        <tbody>
        `;

        // Barcodezeilen hinzufügen
        sortedProdukte.forEach(produkt => {
            // Barcode-Text im Code 39 Format mit * am Anfang/Ende
            const barcodeText = `*${produkt.EAN}*`;
            html += `
                <tr>
                    <td class="links">${produkt.Bezeichnung}</td>
                    <td class="rechts">${produkt.Preis} €</td>
                </tr>
                <tr padding-top: 20px;">
                    <td colspan="2" class="barcode" >${barcodeText}</td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                    <button class="no-print" style="position: absolute; top: 10px; right: 10px;" onclick="window.print();">drucken</button>
                </div>
            </body>
            </html>
        `;

        // HTML in neues Fenster schreiben
        printWindow.document.write(html);
        printWindow.document.close();
    }

    /**
     * Erstellt druckbare Preisliste aller Produkte (außer manuelle Buchungen)
     * Gruppiert nach Kategorien und Sortierung
     */
    function Preisliste_drucken() {
        // Öffne neues Fenster mit der Preisliste
        let printWindow = window.open('', '_blank', 'width=800,height=600');
        
        // HTML für die Preisliste erstellen
        let html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Preisliste</title>
                <link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
                <link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">
            </head>
            <body>
                <div style="text-align: center;">
                    <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">
                    <h1>Preisaushang</h1>
                    <p>Stand: ${heute.toLocaleDateString('de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' })}</p>
                </div>
                <div class="preisliste-print">
                    <table class="preisliste-table">
                        <tbody>
        `;

        // Produkte nach Kategorie sortieren
        const sortedProdukte = [...produkte]
            .filter(p => p.Bezeichnung !== 'manuelle Buchung' && p.Bezeichnung !== 'Essen')
            .sort((a, b) => {
            if (a.Kategorie === b.Kategorie) {
            // Numerischer Vergleich der Sortierungswerte
            return parseInt(a.Sortierung) - parseInt(b.Sortierung);
            }
            return a.Kategorie.localeCompare(b.Kategorie);
            });

        let currentKategorie = '';
        sortedProdukte.forEach(produkt => {
            if (currentKategorie !== produkt.Kategorie) {
                
                currentKategorie = produkt.Kategorie;
                    html += `
                        <tr class="kategorie-trenner" >
                            <td colspan="2" style="padding-top: 20px;"><b><i>${produkt.Kategorie}</i></b></td>
                        </tr>
                    `;
            }
            html += `
                <tr>
                    <td class="links">${produkt.Bezeichnung}</td>
                    <td class="rechts">${produkt.Preis} €</td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                    <button onclick="window.print();">drucken</button>
                </div>
            </body>
            </html>
        `;

        // HTML in neues Fenster schreiben
        printWindow.document.write(html);
        printWindow.document.close();
    }

    /**
     * Erstellt Preisliste mit Barcodes (Code 39) für alle Produkte
     * Ermöglicht Scannen der Produkte an der Kasse
     */
    function Preisliste_strichcode() {

        // Öffne neues Fenster mit der Preisliste
        let printWindow = window.open('', '_blank', 'width=800,height=600');
        
        // HTML für die Preisliste erstellen
        let html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Preisliste</title>
                <link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
                <link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">
                
            </head>
            <body>
                <div style="text-align: center;">
                    <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">
                    <h1>Preisaushang</h1>
                    <p>Stand: ${heute.toLocaleDateString('de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' })}</p>
                </div>
                <div class="preisliste-print">
                    <table class="preisliste-table">
                        <tbody>
        `;

        // Produkte nach Kategorie sortieren
        const sortedProdukte = [...produkte]
            .filter(p => p.Bezeichnung !== 'manuelle Buchung' && p.Bezeichnung !== 'Essen')
            .sort((a, b) => {
            if (a.Kategorie === b.Kategorie) {
            // Numerischer Vergleich der Sortierungswerte
            return parseInt(a.Sortierung) - parseInt(b.Sortierung);
            }
            return a.Kategorie.localeCompare(b.Kategorie);
            });

        let currentKategorie = '';
        sortedProdukte.forEach(produkt => {
            if (currentKategorie !== produkt.Kategorie) {
                
                currentKategorie = produkt.Kategorie;
                    html += `
                        <tr class="kategorie-trenner" >
                            <td colspan="3" style="padding-top: 20px;"><b><i>${produkt.Kategorie}</i></b></td>
                        </tr>
                    `;
            }
            const barcodeText = `*${produkt.EAN}*`;
            html += `
                <tr>
                    <td class="links">${produkt.Bezeichnung}</td>
                    <td class="rechts">${produkt.Preis} €</td>
                </tr>
                <tr padding-top: 20px;">
                    <td colspan="2" class="barcode" >${barcodeText}</td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                    <button onclick="window.print();">drucken</button>
                </div>
            </body>
            </html>
        `;

        // HTML in neues Fenster schreiben
        printWindow.document.write(html);
        printWindow.document.close();
    }   

// ============================================================================
// WARENBESTAND & WARENVERWALTUNG
// ============================================================================

    /**
     * Berechnet aktuellen Warenbestand
     * Addiert Wareneingang, subtrahiert Verkäufe
     * @returns {Array} Array mit EAN und Bestand für jedes Produkt
     */
    function Warenbestand() {
        warenbestand = [];
        
        // Zuerst Wareneingang summieren
        let tempBestand = {};
        wareneingang.forEach(waren => {
            if (!tempBestand[waren.EAN]) {
                tempBestand[waren.EAN] = 0;
            }
            tempBestand[waren.EAN] += parseInt(waren.Menge);
        });

        // Verkäufe abziehen
        verkäufe.forEach(verkauf => {
            if (tempBestand[verkauf.EAN]) {
                tempBestand[verkauf.EAN] -= 1;
            }
            else {
                tempBestand[verkauf.EAN] = -1;
            }
        });

        // Ergebnisse in das warenbestand Array übertragen
        warenbestand = produkte.map(produkt => ({
            EAN: produkt.EAN,
            Bestand: tempBestand[produkt.EAN] || 0
        }));

        return warenbestand;
    }

    /**
     * Verwaltet Wareneingänge in editierbarer Tabelle
     * Ermöglicht Hinzufügen, Bearbeiten und Löschen von Wareneingangsposten
     */
    function Wareneingang() {

        let menu2 = "<h2 style='display: inline;' >Wareneingang</h2>";
        menu2 += `
            <button id="addButton" class="kleinerBt" >hinzufügen</button>
            <button id="saveButton" class="kleinerBt" >speichern</button>
            <button onclick="location.reload();" class="kleinerBt" >abbruch</button>
        `;
        let html = "";
        html += `
            <table id="dataTable" class="portal-table">
                <thead><tr id="tableHeader"></tr></thead>
                <tbody id="tableBody"></tbody>
            </table>
        `;

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

        createEditableTable(wareneingang); // Tabelle erstellen und HTML einfügen
                
        //Funktion zum Erstellen der Tabelle
    
        function createEditableTable(initialData) {
            let data = JSON.parse(JSON.stringify(initialData)); // Kopie für Bearbeitung
            let originalData = JSON.parse(JSON.stringify(initialData)); // Für Undo
            let editedRows = new Set();
            let newRows = new Set();
            let deletedRows = new Set();
            // Verwende die zentral definierten Spalten für Wareneingang
            let keys = wareneingangKeys;

            const tableHeader = document.getElementById("tableHeader");
            const tableBody = document.getElementById("tableBody");
            const addButton = document.getElementById("addButton");
            const saveButton = document.getElementById("saveButton");

            renderHeader();
            renderTable();

            function renderHeader() {
                tableHeader.innerHTML = "";
                keys.forEach(key => {
                    const th = document.createElement("th");
                    th.innerText = key;
                    tableHeader.appendChild(th);
                    if (key === "EAN") {
                        const th1 = document.createElement("th");
                        th1.innerText = "Bezeichnung";
                        tableHeader.appendChild(th1);
                        const th2 = document.createElement("th");
                        th2.innerText = "Kategorie";
                        tableHeader.appendChild(th2);
                        const th3 = document.createElement("th");
                        th3.innerText = "Preis";
                        tableHeader.appendChild(th3);
                    };
                });
         
                const actionTh = document.createElement("th");
                actionTh.innerText = "Aktionen";
                tableHeader.appendChild(actionTh);
            }

            function markAsEdited(index, key, newValue, tdElement) {
                if (data[index][key] !== newValue) {
                    data[index][key] = newValue;
                    if (!newRows.has(index)) {
                        editedRows.add(index);
                    }
                    tdElement.classList.add("edited");
                }
                renderTable();
            }

            function markAsEdited(index, key, newValue, tdElement) {
                if (data[index][key] !== newValue) {
                    data[index][key] = newValue;
                    if (!newRows.has(index)) {
                        editedRows.add(index);
                    }
                    tdElement.classList.add("edited");
                }
                renderTable();
            }

            function renderTable() {
                tableBody.innerHTML = "";
                data.forEach((item, index) => {
                    const tr = document.createElement("tr");
                    tr.classList.toggle("edited", editedRows.has(index));
                    tr.classList.toggle("new", newRows.has(index));
                    tr.classList.toggle("deleted", deletedRows.has(index));

                    keys.forEach(key => {
                        
                        const td = document.createElement("td");
                        
                        if (key === "Eingang") {
                            
                            const datumInput = document.createElement("input"); 
                            datumInput.type = "date";
                            datumInput.value = item[key] || heute.toISOString().split('T')[0]; // Default to today if no value
                            datumInput.disabled = deletedRows.has(index);
                            datumInput.onchange = () => markAsEdited(index, key, datumInput.value, td);
                            td.appendChild(datumInput);
                        } else if (key === "Menge") {
                            td.contentEditable = !deletedRows.has(index);
                            td.className = 'inputfeld';
                            td.innerText = item[key];
                            td.onblur = () => {
                                const newValue = td.innerText;
                                if (!Number.isInteger(Number(newValue)) || newValue === "") {
                                    td.innerText = item[key]; // Restore old value
                                    alert("Bitte geben Sie eine ganze Zahl ein.");
                                } else {
                                    markAsEdited(index, key, newValue, td);
                                }
                            };
                        } else {
                            if (key === "EAN") {
                                td.contentEditable = false;
                            } else {
                                td.contentEditable = !deletedRows.has(index);
                            }
                            if (deletedRows.has(index)) {
                                td.classList.add("text");
                            }
                            td.innerText = item[key];
                            td.onblur = () => markAsEdited(index, key, td.innerText, td);
                        }
                        tr.appendChild(td);

                        if (key === "EAN") {
                            const bezeichnungTd = document.createElement("td");
                            const select = document.createElement("select");
                            select.innerHTML = `<option value="">-- Produkt wählen --</option>
                                ${produkte.sort((a,b) => a.Bezeichnung.localeCompare(b.Bezeichnung)).map(p => 
                                    `<option value="${p.EAN}" ${item[key] == p.EAN ? 'selected' : ''}>${p.Bezeichnung}</option>`
                                ).join('')}`;
                            select.onchange = (e) => markAsEdited(index, key, e.target.value, bezeichnungTd);
                            select.disabled = deletedRows.has(index);
                            bezeichnungTd.appendChild(select);
                            tr.appendChild(bezeichnungTd);
                            
                            // Kategorie und Preis nur anzeigen, wenn ein Produkt ausgewählt ist
                            const kategorieTd = document.createElement("td");
                            const preisTd = document.createElement("td");
                            
                            if (item[key]) {
                                const produkt = produkte.find(p => p.EAN == item[key]);
                                if (produkt) {
                                    kategorieTd.innerText = produkt.Kategorie;
                                    preisTd.innerText = produkt.Preis + " €";
                                } else {
                                    kategorieTd.innerText = "";
                                    preisTd.innerText = " €";
                                }
                            } else {
                                kategorieTd.innerText = "";
                                preisTd.innerText = " €";
                            }
                            
                            tr.appendChild(kategorieTd);
                            tr.appendChild(preisTd);
                        }
                    });

                    const actionTd = document.createElement("td");
                    const deleteBtn = document.createElement("a");
                    deleteBtn.href = "#";
                    deleteBtn.classList.add("icon");
                    deleteBtn.innerHTML = deletedRows.has(index) ? "🔄" : "🗑️";
                    deleteBtn.onclick = () => toggleDeleteRow(index);
                    actionTd.appendChild(deleteBtn);

                    if (editedRows.has(index) || deletedRows.has(index) || newRows.has(index)) {
                        const undoBtn = document.createElement("a");
                        undoBtn.href = "#";
                        undoBtn.classList.add("icon");
                        undoBtn.innerHTML = "↩️";
                        undoBtn.onclick = () => undoChange(index);
                        actionTd.appendChild(undoBtn);
                    }

                    tr.appendChild(actionTd);
                    tableBody.appendChild(tr);
                });
            }

            function markAsEdited(index, key, newValue, tdElement) {
                if (data[index][key] !== newValue) {
                    data[index][key] = newValue;
                    if (!newRows.has(index)) {
                        editedRows.add(index);
                    }
                    tdElement.classList.add("edited");
                }
                renderTable();
            }

            function addRow() {
                const newItem = {};
                keys.forEach(key => newItem[key] = "");
                const newIndex = data.length;
                data.push(newItem);
                originalData.push(JSON.parse(JSON.stringify(newItem)));
                newRows.add(newIndex);
                renderTable();
            }

            function toggleDeleteRow(index) {
                if (deletedRows.has(index)) {
                    deletedRows.delete(index);
                } else {
                    deletedRows.add(index);
                }
                renderTable();
            }

            function undoChange(index) {
                if (newRows.has(index)) {
                    data.splice(index, 1);
                    originalData.splice(index, 1);
                    newRows.delete(index);
                } else {
                    data[index] = JSON.parse(JSON.stringify(originalData[index]));
                    editedRows.delete(index);
                    deletedRows.delete(index);
                }

                renderTable();
        }

            function saveChanges() {
                const savedData = data.filter((_, index) => !deletedRows.has(index));

                // Nach dem Speichern, neue Ausgangsdaten setzen
                data = JSON.parse(JSON.stringify(savedData));
                originalData = JSON.parse(JSON.stringify(savedData));
                editedRows.clear();
                newRows
                deletedRows.clear();
                renderTable();

                return savedData;
                }

                addButton.onclick = addRow;

                saveButton.onclick = () => {

                    const updatedData = saveChanges();
                    wareneingang = updatedData; // Aktualisiere die wareneingang-Variable

                    fetch('json-schreiben.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            data: wareneingang,
                            filename: 'daten/wareneingang.json'
                        })
                    })
                    .then(response => response.text())
                    .then(result => {
                        alert('Wareneingangstabelle erfolgreich gespeichert:', result);
                    })
                    .catch(error => {

                    });

            };

        } 
    }

// ============================================================================
// HILFSFUNKTIONEN & UTILITIES
// ============================================================================

    /**
     * Zeigt verwendete Farbpalette aus farben.php
     */
    function Farben() {

        portalmenu2.innerHTML = "<h2 style='display: inline;'>verwendete Farbpalette</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, die Farben werden geladen...</p>";
        
        console.log("Farben laden...");
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "farben.php", true); 
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                portalInhalt.innerHTML = xhr.responseText;
            }
        };
        xhr.send();

    }

    /**
     * Escaped HTML-Sonderzeichen zur sicheren Anzeige
     * @param {string} text - Zu escapender Text
     * @returns {string} HTML-sicherer Text
     */
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /**
     * Zeigt Tagesübersicht der Umsätze pro Kunde
     * Listet nur Kunden mit Umsätzen am heutigen Tag
     */
    function Kundentagesübersicht() {

        portalmenu2.innerHTML = "<h2 style='display: inline;'>Kundentagesübersicht</h2>";

        let html = "<table class='portal-table'>";

        console.log("Kundentagesübersicht laden...");
        console.log("Käufer", käufer);

        html = "";

        let datensatzsichtbar = false;

        käufer.forEach(kunde => {
            console.log("Kunde:", kunde);
            let summe= 0;
            let htmlkunde = ""
            htmlkunde += "<table class='portal-table'>";
            htmlkunde += "<tr><th colspan='5' class='links'>" + kunde.firstname + " " + kunde.lastname + " - " + kunde.uid + "</th></tr>";

            verkäufe.filter(verkauf => verkauf.Kundennummer == kunde.uid && verkauf.Datum == heute.toISOString().split('T')[0]).forEach(verkauf => {
            
                summe += parseFloat(verkauf.Preis);
                htmlkunde += `
                    <tr>
                        <td>${verkauf.Terminal}</td>
                        <td>${verkauf.Datum}</td>
                        <td>${verkauf.Zeit}</td>
                        <td class="links">${verkauf.Produkt}</td>
                        <td class="rechts">${verkauf.Preis} €</td>
                    </tr>
                `;
                datensatzsichtbar = true;
            });
            htmlkunde += `
                <tr class="summenzeile">
                    <td colspan="4" class="rechts"><b>Summe</b></td>
                    <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
                </tr>
                </table>

            `;

            if (datensatzsichtbar) html += htmlkunde;
            datensatzsichtbar = false; // Reset für den nächsten Kunden
           
        });
        html += '</table>';
        portalInhalt.innerHTML = html;

    }    

// ============================================================================
// PRODUKTVERWALTUNG
// ============================================================================

    /**
     * Verwaltet Produktkatalog in editierbarer Tabelle
     * Zeigt auch aktuellen Warenbestand, ermöglicht Sortierung
     */
    function Produkte_editieren() {
       
        let menu2 = "<h2 style='display: inline;''>Produktkatalog editieren</h2>";
        menu2 += `
            <button id="addButton" class="kleinerBt" >hinzufügen</button>
            <button id="saveButton" class="kleinerBt" >speichern</button>
            <button onclick="location.reload();" class="kleinerBt" >abbruch</button>
        `;
        
        let html = "";
         html += `
            <table id="dataTable" class="portal-table">
                <thead><tr id="tableHeader"></tr></thead>
                <tbody id="tableBody"></tbody>
            </table>
        `;

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

        // Aktuellen Warenbestand berechnen
        let currentWarenbestand = Warenbestand();

        // Bestand als zusätzliches Feld zu Produkten hinzufügen
        let productsWithBestand = produkte.map(produkt => {
            let bestand = currentWarenbestand.find(w => w.EAN === produkt.EAN)?.Bestand || 0;
            return {...produkt, Bestand: bestand};
        });

        createEditableTable(productsWithBestand);

        function createEditableTable(initialData) {
            let data = JSON.parse(JSON.stringify(initialData));
            let originalData = JSON.parse(JSON.stringify(initialData));
            let editedRows = new Set();
            let newRows = new Set();
            let deletedRows = new Set();
            // Verwende die zentral definierten Spalten
            let keys = produktKeys;
            let sortColumn = '';
            let sortAscending = true;

            const tableHeader = document.getElementById("tableHeader");
            const tableBody = document.getElementById("tableBody");
            const addButton = document.getElementById("addButton");
            const saveButton = document.getElementById("saveButton");

            function markAsEdited(index, key, newValue, tdElement) {
                if (data[index][key] !== newValue) {
                    data[index][key] = newValue;
                    if (!newRows.has(index)) {
                        editedRows.add(index);
                    }
                    tdElement.classList.add("edited");
                }
                renderTable();
            }
            
            function renderHeader() {
                tableHeader.innerHTML = "";
                keys.forEach(key => {
                    const th = document.createElement("th");
                    th.innerText = key;
                    th.style.cursor = 'pointer';
                    th.onclick = () => {
                        if (sortColumn === key) {
                            sortAscending = !sortAscending;
                        } else {
                            sortColumn = key;
                            sortAscending = true;
                        }
                        data.sort((a, b) => {
                            let valueA = a[key];
                            let valueB = b[key];
                            
                            // Check if values can be converted to numbers
                            const numA = Number(valueA);
                            const numB = Number(valueB);
                            
                            if (!isNaN(numA) && !isNaN(numB)) {
                                // Numeric sorting
                                return sortAscending ? numA - numB : numB - numA;
                            } else {
                                // String sorting
                                if (typeof valueA === 'string') valueA = valueA.toLowerCase();
                                if (typeof valueB === 'string') valueB = valueB.toLowerCase();
                                if (valueA < valueB) return sortAscending ? -1 : 1;
                                if (valueA > valueB) return sortAscending ? 1 : -1;
                                return 0;
                            }
                        });
                        renderTable();
                        renderHeader(); 

                    };
                    if (key === sortColumn) {
                        th.innerText += sortAscending ? ' ▲' : ' ▼';
                    }
                    tableHeader.appendChild(th);
                });
                const actionTh = document.createElement("th");
                actionTh.innerText = "Aktionen";
                tableHeader.appendChild(actionTh);
            }

            function renderTable() {
                tableBody.innerHTML = "";
                data.forEach((item, index) => {
                    
                    if (item.EAN == 1 || item.EAN == 9990000000000) {return;} // Essen und manuelle Buchung nicht anzeigen

                    const tr = document.createElement("tr");
                    tr.classList.toggle("edited", editedRows.has(index));
                    tr.classList.toggle("new", newRows.has(index));
                    tr.classList.toggle("deleted", deletedRows.has(index));

                    keys.forEach(key => {
                        const td = document.createElement("td");
                        // Zahlenfelder rechts ausrichten
                        if (['Bestand', 'Preis', 'Sortierung', 'MwSt', 'EAN', 'Min', "Zählerstand"].includes(key)) {
                            td.classList.add('rechts');
                        } else {
                            td.classList.add('links'); 
                        }

                        if (key === 'Menge') {
                            const label = document.createElement('label');
                            label.className = 'switch';
                            
                            const input = document.createElement('input');
                            input.type = 'checkbox';
                            input.checked = item[key] === "true";
                            input.disabled = deletedRows.has(index);
                            
                            const span = document.createElement('span');
                            span.className = 'slider round';
                            
                            label.appendChild(input);
                            label.appendChild(span);
                            td.appendChild(label);

                            input.onchange = () => {
                                markAsEdited(index, key, input.checked.toString(), td);
                            };

                        } else if (key === 'Bestand') {
                            // Bestand ist editierbar - Änderungen werden automatisch als Wareneingang gebucht
                            td.contentEditable = !deletedRows.has(index); 

                            //Sollte kein "Min" Wert gesetzt sein, dann soll der Bestand leer sein
                            if (item.Min == 0) {
                                td.innerText =  '';
                            }
                            else {
                                td.innerText = item[key] || '';
                            }
                            
                            // Prüfe ob Bestand unter Mindestbestand
                            if (item['Min'] && item['Min'] != 0 && parseInt(item[key] || 0) < parseInt(item['Min'])) {
                                td.style.backgroundColor = '#ffcccc';
                            }
                            td.onblur = () => {
                                if (isNaN(parseFloat(td.innerText)) && td.innerText !== '') {
                                    alert('Bitte geben Sie eine gültige Zahl ein.');
                                    td.innerText = item[key] || '';
                                    return;
                                }
                                const newValue = td.innerText === '' ? 0 : parseInt(td.innerText);
                                const oldValue = item[key] || 0;
                                if (newValue !== oldValue) {
                                    const diff = newValue - oldValue;
                                    if (diff !== 0) {
                                        const wareneingangEntry = {
                                            Eingang: heute.toISOString().split('T')[0],
                                            EAN: item.EAN,
                                            Menge: diff
                                        };
                                        wareneingang.push(wareneingangEntry);
                                    }
                                    markAsEdited(index, key, newValue, td);
                                }
                                // Update Hintergrundfarbe nach Änderung
                                if (item['Min'] && newValue < parseInt(item['Min'])) {
                                    td.style.backgroundColor = '#ffcccc';
                                } else {
                                    td.style.backgroundColor = '';
                                }
                            };
                        } else if (['Preis', 'Sortierung', 'MwSt', 'EAN', 'Min'].includes(key)) {
                            td.contentEditable = !deletedRows.has(index);
                            if (key === 'Min' && item[key] == 0) {
                                td.innerText = '';
                            } else {
                                td.innerText = item[key] || '';
                            }
 
                            td.onblur = () => {
                                if (isNaN(parseFloat(td.innerText)) && td.innerText !== '') {
                                    alert(`Bitte geben Sie eine gültige Zahl für ${key} ein.`);
                                    td.innerText = item[key];
                                    return;
                                }
                                if (key === 'Preis') {
                                    let input = td.innerText.trim();

                                    // Komma durch Punkt ersetzen
                                    const normalizedInput = input.replace(',', '.');

                                    const priceRegex = /^\d+([.,]\d{2})?$/;

                                    if (!priceRegex.test(input) || parseFloat(normalizedInput) < 0) {
                                        alert('Der Wert muss eine positive Zahl im Format 0.00 oder 0,00 sein.');
                                        td.innerText = item[key]; // Ursprünglichen Wert wiederherstellen
                                        return;
                                    }

                                    // Formatierung erzwingen: 2 Nachkommastellen, Punkt statt Komma
                                    const number = parseFloat(normalizedInput);
                                    td.innerText = number.toFixed(2); // z. B. "12.00"
                                }
                                if (key === 'EAN') {
                                    const newEAN = td.innerText;
                                    const isDuplicate = data.some((dataItem, dataIndex) => 
                                        dataIndex !== index && dataItem.EAN === newEAN
                                    );
                                    if (isDuplicate || newEAN === '1' || newEAN === '9990000000000' || newEAN === '9999') {
                                        alert('Diese EAN existiert bereits! Auch 1, 9990000000000 und 9999 sind nicht erlaubt. Bitte wählen Sie eine andere EAN.');
                                        td.innerText = item[key];
                                    } else {
                                        markAsEdited(index, key, newEAN, td);
                                    }
                                } else {
                                    markAsEdited(index, key, td.innerText, td);
                                }
                            };
                        } else {
                            td.contentEditable = !deletedRows.has(index);
                            td.innerText = item[key];
                            td.onblur = () => markAsEdited(index, key, td.innerText, td);
                        }
                        if (deletedRows.has(index)) {
                            td.classList.add("text");
                        }
                        tr.appendChild(td);
                    });

                    const actionTd = document.createElement("td");
                    const deleteBtn = document.createElement("a");
                    deleteBtn.href = "#";
                    deleteBtn.classList.add("icon");
                    deleteBtn.innerHTML = deletedRows.has(index) ? "🔄" : "🗑️";
                    deleteBtn.onclick = () => toggleDeleteRow(index);
                    actionTd.appendChild(deleteBtn);

                    if (editedRows.has(index) || deletedRows.has(index) || newRows.has(index)) {
                        const undoBtn = document.createElement("a");
                        undoBtn.href = "#";
                        undoBtn.classList.add("icon");
                        undoBtn.innerHTML = "↩️";
                        undoBtn.onclick = () => undoChange(index);
                        actionTd.appendChild(undoBtn);
                    }

                    tr.appendChild(actionTd);
                    tableBody.appendChild(tr);
                });
            }

            function toggleDeleteRow(index) {
                if (deletedRows.has(index)) {
                    deletedRows.delete(index);
                } else {
                    deletedRows.add(index);
                }
                renderTable();
            }

            function undoChange(index) {
                if (newRows.has(index)) {
                    data.splice(index, 1);
                    originalData.splice(index, 1);
                    newRows.delete(index);
                } else {
                    data[index] = JSON.parse(JSON.stringify(originalData[index]));
                    editedRows.delete(index);
                    deletedRows.delete(index);
                }
                renderTable();
            }

            function saveChanges() {
                const savedData = data.filter((_, index) => !deletedRows.has(index));
                data = JSON.parse(JSON.stringify(savedData));
                originalData = JSON.parse(JSON.stringify(savedData));
                editedRows.clear();
                newRows.clear();
                deletedRows.clear();
                renderTable();
                return savedData;
            }

            addButton.onclick = () => {
                const newItem = {};
                keys.forEach(key => newItem[key] = "");
                const newIndex = data.length;
                data.push(newItem);
                originalData.push(JSON.parse(JSON.stringify(newItem)));
                newRows.add(newIndex);
                renderTable();
            };

            saveButton.onclick = () => {
                const updatedData = saveChanges();
                produkte = updatedData.map(({Bestand, ...rest}) => rest);

                fetch('json-schreiben.php', {

                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        data: produkte,
                        filename: 'daten/produkte.json'
                    })
                })
                .then(response => response.text())
                .then(() => {

                    return fetch('json-schreiben.php', {

                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            data: wareneingang,
                            filename: 'daten/wareneingang.json'
                        })
                    });
                })
                .then(() => {
                    alert('Produktkatalog und Wareneingang erfolgreich gespeichert');
                })
                .catch(error => {
                    alert('Fehler beim Speichern:', error);
                });
            };

            renderHeader();
            renderTable();
        }
    }

// ============================================================================
// MITGLIEDERVERWALTUNG
// ============================================================================

    /**
     * Zeigt Liste aller Mitglieder mit Kontoinformationen
     * Listet ID, Mitgliedsnr, Name, Email, Rollen, Kontostand
     */
    function Mitgliederdaten_anzeigen() {

        let menu2 = `<h2 style='display: inline;'>Kundenliste</h2> 
                    Rolle: A = Administrator / V = Verkäufer / M = Mitglied / G = Gast 
                    <button class='kleinerBt' style='width: auto;' onclick='Mitgliedsdaten_ziehen()'>aus VF aktualisieren</button>
                    <button class='kleinerBt' style='width: auto;' onclick='MitgliederStrichcodeliste()'>Strichcodeliste</button>
                    <button class='kleinerBt' style='width: auto;' onclick='MitgliederAusweise()'>Bezahlkarten</button>
                    `;

        let html = '<table class="portal-table">';
        html += `
        <tr>
            <th>ID</th>
            <th class="links">Mitgliedsnr</th>
            <th class="links">Vorname</th>
            <th class="links">Nachname</th>
            <th class="links">Email</th>
            <th>Schlüssel</th>
            <th>A</th>
            <th>V</th>
            <th>M</th>
            <th>G</th>
            <th>Kontostand</th>
            <th><i>i</i></th>
        </tr>`;

        kunden.forEach(kunde => {
            html += `<tr>
                <td>${kunde.uid}</td>
                <td class="links">${kunde.memberid}</td>
                <td class="links">${kunde.firstname}</td>
                <td class="links">${kunde.lastname}</td>
                <td class="links">${kunde.email}</td>
                <td>${kunde.schlüssel}</td>`
                
                html += kunde.cc_admin ? "<td>✔️</td>" : "<td></td>";
                html += kunde.cc_seller ? "<td>✔️</td>" : "<td></td>";
                html += kunde.cc_member ? "<td>✔️</td>" : "<td></td>";
                html += kunde.cc_guest ? "<td>✔️</td>" : "<td></td>";
                html += `<td class="rechts">${(-kunde.Kontostand).toFixed(2)} €</td>`;
                html += `<td><a style='text-decoration: none;' href='#' onclick='Kundenübersicht(${kunde.uid})'>ℹ️</a></td>`;                
                html += "</tr>";
        });

        html += '</table>';

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

    }	

    /**
     * Erstellt druckbare Barcode-Liste aller Mitglieder
     * Sortiert nach Nachnamen, zeigt Code 39 Barcodes
     */
    function MitgliederStrichcodeliste() {

        // Neues Fenster für die Strichcodeliste öffnen
        let printWindow = window.open('', '_blank', 'width=800,height=600');

        // Mitgleiderliste nach Nachnamen und Vornamen sortieren
        let sortedKunden = [...kunden].sort((a, b) => {
            if (a.lastname.toLowerCase() < b.lastname.toLowerCase()) return -1;
            if (a.lastname.toLowerCase() > b.lastname.toLowerCase()) return 1;
            if (a.firstname.toLowerCase() < b.firstname.toLowerCase()) return -1;
            if (a.firstname.toLowerCase() > b.firstname.toLowerCase()) return 1;
            return 0;
        });

        // HTML-Inhalt für die Strichcodeliste erstellen
        let html = `
            <html>
            <head>
                <title>Mitglieder Bezahlcodeliste</title>
                <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
	            <link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">
            </head>
            <body>
                 <div style="text-align: center;">
                    <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; margin: 30px;">
                    <h1>Mitglieder Bezahlcode</h1>
                    <p>Stand: ${heute.toLocaleDateString('de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' })}</p>
                </div>
                <div  style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; width: 100%; margin-bottom: 20px;">
                `;

        sortedKunden.forEach(kunde => {
            html += `   
                    <div class="no-break">
                        <div style="font-size: 1.5em;">${kunde.lastname}, ${kunde.firstname} </div>
                        <div class="barcode" style="font-size: 3em;">*$${kunde.schlüssel}*</div>
                    </div>`;
        });
        html += `
                </div>
            </body>
            </html>`;
        printWindow.document.write(html);
        printWindow.document.close();
    }

    /**
     * Erstellt Mitglieder-Bezahlkarten im Kreditkartenformat (8.5 x 5.5 cm)
     * @param {string} schlüsselDruck - Optional: Nur eine bestimmte Karte drucken
     */
    function MitgliederAusweise(schlüsselDruck) {

        // Neues Fenster für die Ausweise öffnen
        let printWindow = window.open('', '_blank', 'width=800,height=600');

        // Mitgleiderliste nach Nachnamen und Vornamen sortieren
        let sortedKunden = [...käufer].sort((a, b) => {
            if (a.lastname.toLowerCase() < b.lastname.toLowerCase()) return -1;
            if (a.lastname.toLowerCase() > b.lastname.toLowerCase()) return 1;
            if (a.firstname.toLowerCase() < b.firstname.toLowerCase()) return -1;
            if (a.firstname.toLowerCase() > b.firstname.toLowerCase()) return 1;
            return 0;
        });

        // HTML-Inhalt für die Strichcodeliste erstellen
        let html = `
            <html>
            <head>
                <title>Mitglieder Bezahlcodeliste</title>
                <link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
	            <link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">
            </head>
            <body>
                <button style="position: absolute; top: 10px; right: 10px;" onclick="window.print();">drucken</button>
                <div style="text-align: center;">
                    <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; margin: 30px;">
                    <h1>Mitglieder Bezahlkarten</h1>
                    <p>Stand: ${heute.toLocaleDateString('de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' })}</p>
                </div>
                <div  style="display: flex; flex-wrap: wrap;">
                `;
        
        // Für jedes Mitglied einen Ausweis erstellen

        // Prüfen, ob nnameDruck definiert sind, dann nur die die Einzelne Mitgliedskarte drucken
        if (schlüsselDruck) {
            sortedKunden = sortedKunden.filter(kunde => kunde.schlüssel && kunde.schlüssel.trim() == schlüsselDruck);
        }

        sortedKunden.forEach(kunde => {
            html += `
                    <div class="no-break" style="border: 1px solid black; margin: 0px; padding: 0px; width: 8.5cm; height: 5.5cm;">
                        <div style="display: grid; grid-template-columns: 1fr 3fr; align-items: center; padding: 10px; gap: 10px;">    
                            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 75px; margin: 10px;">
                            <div style="text-align: center; font-size: 1.0em; ">
                                <b>${config.Vereinsname}</b><br>
                                <span>Bezahlkarte</span><br>
                            </div>
                        </div>        
                        <div style="font-size: 1.2em; margin: 15px; text-align: center;">${kunde.lastname}, ${kunde.firstname} </div>
                        <div class="barcode" style="font-size: 2em; text-align: center;">*${kunde.schlüssel}*</div>  

                    </div>`;
        });
        html += `
                </div>
            </body>
            </html>`;
        printWindow.document.write(html);
        printWindow.document.close();
    }   

// ============================================================================
// KONTOVERWALTUNG & FINANZEN
// ============================================================================

    /**
     * Gleicht Kontostand eines Kunden aus (setzt auf 0)
     * Erstellt Buchung mit Terminal 'Z' und EAN 9999999999
     * @param {string} kundennummer - ID des Kunden
     * @param {number} betrag - Auszugleichender Betrag
     */
    function KontoAusgleichen(kundennummer, betrag) {
        let menu2 = `<h2 style='display: inline;'>Konto ausgleichen</h2>`;
        let kunde = käufer.find(k => k.uid == kundennummer);
        
        if (!kunde) {
            portalmenu2.innerHTML = menu2;
            portalInhalt.innerHTML = '<p>Fehler: Kunde nicht gefunden</p>';
            return;
        }

        // Kontostand in eine Zahl umwandeln
        const betrag1 = parseFloat(betrag).toFixed(2);
        const betrag2 = parseFloat(-betrag).toFixed(2);

        if (!confirm(`Soll der Kontostand von ${kunde.firstname} ${kunde.lastname} in Höhe von ${betrag1} € ausgeglichen werden?`)) {
            return;
        }

        // Umbuchung in der API durchführen
        fetch('./kasse/umsatz-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify([
                {
                    Datum: heute.toISOString().split('T')[0],
                    Zeit: heute.toTimeString().split(':').slice(0,2).join(':'),
                    Terminal: 'Z',
                    Schlüssel: '9999999999',
                    Kundennummer: kundennummer,
                    EAN: '9999999999',
                    Produkt: `Kontoausgleich`,
                    Kategorie: 'Buchung',
                    Preis: betrag2,
                    MwSt: 0 // Keine Steuern für Kontoausgleich
                }
            ])
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Netzwerk-Antwort war nicht ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === "success") {
                window.alert(`Kontostand erfolgreich ausgeglichen: ${betrag1} € von ${kunde.firstname} ${kunde.lastname}`);
                location.reload();
            } else {
                throw new Error(data.message || 'Unbekannter Fehler');
            }
        })
        .catch(error => {
            portalmenu2.innerHTML = menu2;
            portalInhalt.innerHTML = `<p>Fehler beim Ausgleichen des Kontostands: ${error.message}</p>`;
        });
    }

    /**
     * Bucht Kontostand eines externen Kunden auf Hauptkonto um
     * @param {string} kundennummer - ID des externen Kunden
     * @param {string} beziehunguid - ID des Hauptkunden
     * @param {string} vorname - Vorname des externen Kunden
     * @param {string} nachname - Nachname des externen Kunden
     * @param {number} kontostand - Umzubuchender Betrag
     */
    function KontostandUmbuchen(kundennummer, beziehunguid, vorname, nachname, kontostand) {
        let menu2 = `<h2 style='display: inline;'>Kontostand umbuchen</h2>`;

        let hauptkunde = käufer.find(k => k.uid == beziehunguid);

        // Kontostand in eine Zahl umwandeln

        const betrag1 = parseFloat(kontostand).toFixed(2);
        const betrag2 = parseFloat(-kontostand).toFixed(2);

        if (!confirm(`Soll der Kontostand von ${vorname} ${nachname} in Höhe von ${betrag1} € zu ${hauptkunde.firstname} ${hauptkunde.lastname} übertragen werden?`)) {
            return;
        }

        // Umbuchung in der API durchführen
        fetch('./kasse/umsatz-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify([
                {
                    Datum: heute.toISOString().split('T')[0],
                    Zeit: heute.toTimeString().split(':').slice(0,2).join(':'),
                    Terminal: 'Z',
                    Schlüssel: '9999999999',
                    Kundennummer: beziehunguid,
                    EAN: '9999999999',
                    Produkt: `Kontoausgleich von ${kundennummer} - ${vorname} ${nachname}`,
                    Kategorie: 'Umbuchung',
                    Preis: betrag1,
                    MwSt: 0
                },
                {
                    Datum: heute.toISOString().split('T')[0],
                    Zeit: heute.toTimeString().split(':').slice(0,2).join(':'),
                    Terminal: 'Z',
                    Schlüssel: '9999999999',
                    Kundennummer: kundennummer,
                    EAN: '9999999999',
                    Produkt: `Kontoausgleich zu ${beziehunguid} - ${hauptkunde.firstname} ${hauptkunde.lastname}`,
                    Kategorie: 'Umbuchung',
                    Preis: betrag2,
                    MwSt: 0
                }
            ])
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Netzwerk-Antwort war nicht ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === "success") {
                window.alert(`Kontostand erfolgreich umgebucht: ${betrag1} € von ${vorname} ${nachname} zu ${hauptkunde.firstname} ${hauptkunde.lastname}`);
                location.reload();
            } else {
                throw new Error(data.message || 'Unbekannter Fehler');
            }
        })
        .catch(error => {
            portalmenu2.innerHTML = menu2;
            portalInhalt.innerHTML = `<p>Fehler beim Umbuchen des Kontostands: ${error.message}</p>`;
        });
    }

// ============================================================================
// RECHNUNGEN & BERICHTE
// ============================================================================

    /**
     * Erstellt druckbare Rechnung für einen Kunden und Zeitraum
     * Öffnet neues Fenster mit vollständiger Rechnungsansicht
     * @param {string} kundennummer - ID des Kunden
     * @param {Date} datum1 - Startdatum
     * @param {Date} datum2 - Enddatum
     */
    function RechnungErstellen(kundennummer, datum1, datum2) {
    
        // Datum-Strings in Date-Objekte umwandeln
        if (typeof datum1 === 'string') datum1 = new Date(datum1);
        if (typeof datum2 === 'string') datum2 = new Date(datum2);

        // HTML-Inhalt für die Abrechnung erstellen
        let html = `
            <head>
                <title>Abrechnung</title>
                <link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
                <link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">
            </head>
        `;
        html += RechnungstextErstellen(kundennummer, datum1, datum2);
        html += `<button style="position: absolute; top: 10px; right: 10px; media-print: none;" onclick="window.print();">drucken</button>`;

        // ein neues Fenster für die Abrechnung in der Größe DIN A4 öffnen
        let printWindow = window.open('', 'Abrechnung', 'width=800,height=1000');
        if (!printWindow) {
            console.error('Could not open print window - popup might be blocked');
            window.alert('Popup-Blocker erkannt. Bitte erlauben Sie Popups für diese Seite.');
            return;
        }
          
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.onload = () => {
            printWindow.focus(); // Fokus auf das neue Fenster setzen
        };
        
    }

    /**
     * Zeigt detaillierte Kundenübersicht mit allen Umsätzen
     * Gruppiert nach: Einzelumsätze, Produkte, Produktgruppen
     * @param {string} kundennummer - ID des Kunden
     * @param {Date} datum1 - Startdatum (optional)
     * @param {Date} datum2 - Enddatum (optional)
     */
    function Kundenübersicht(kundennummer,datum1,datum2) {

        let menu2 = "<h2 style='display: inline;'>Übersicht</h2>";
        
        let html = '';

        // die akteullen anmeldedaten holen

        const kunde = käufer.find(kunde => kunde.uid == kundennummer);

        //Kontostand berechnen
        let kundenkontostandeinzeln = kundenkontostand.find(k => k.Kundennummer === kundennummer);

        if (!kunde) {
            portalmenu2.innerHTML = menu2;
            portalInhalt.innerHTML = '<p>Fehler: Kunde nicht gefunden</p>';
            return;
        }

        // Hier kann der Code für die Kundenübersicht fortgesetzt werden
        if(!datum1 || !datum2) {
            let datumjahr = heute.getFullYear();
            datum1 = new Date(datumjahr, 0, 1); // 1. Januar des aktuellen Jahres
            datum1.setHours(datum1.getHours() + 2); // 2 Stunden addieren
            datum2 = heute; // Aktuelles Datum
        } 
 
        let beziehungstext = 'keine';
        if (kunde.bezuguid) {
            let beziehung = käufer.find(k => k.uid === kunde.bezuguid);
            beziehungstext = beziehung.uid + " - " + beziehung.firstname + " " + beziehung.lastname;

            if (angemeldetesMitglied.cc_admin == true) {
                beziehungstext += `<button class='kleinerBt' style='width: auto; margin-left: 10px; background-color: var(--warning-color);' onclick='KontostandUmbuchen("${kunde.uid}", "${beziehung.uid}", "${kunde.firstname}", "${kunde.lastname}", ${kunde.Kontostand})'>Kontostand übertragen</button>`;
            }
        }

        KäufeFilter = verkäufe
            .map((auswahl, index) => ({...auswahl, originalIndex: index}))
            .filter(auswahl => auswahl.Kundennummer == kundennummer && auswahl.Datum >= datum1.toISOString().split('T')[0] && auswahl.Datum <= datum2.toISOString().split('T')[0]);

        console.log(KäufeFilter);

        let summe = 0;        

        menu2 += `
            <input class="DatumInput" type="date" id="datum_anfang" value="${datum1.toISOString().split('T')[0]}">
            <h2 style="display: inline;"> bis </h2>
            <input class="DatumInput" type="date" id="datum_ende" value="${datum2.toISOString().split('T')[0]}">
            <button id="bt_aktualisierung" class="kleinerBt">aktualisieren</button>
            <button class="kleinerBt" onclick="Kundenübersicht(${kunde.uid}, monatsbeginn, heute)">Monat</button>
            <button class="kleinerBt" onclick="Kundenübersicht(${kunde.uid}, wochenbeginn, heute)">Woche</button>
            <button class="kleinerBt" onclick="Kundenübersicht(${kunde.uid}, heute, heute)">Tag</button>
        `;

        html += `

            <button class="kleinerBt" onclick="RechnungErstellen('${kunde.uid}', '${datum1}', '${datum2}')" style="margin-left: 10px;">Abrechnung</button>
            <button class="kleinerBt" onclick="Emailrechnung('${kunde.uid}', '${datum1}', '${datum2}'); alert('Email wurde gesendet.');" style="margin-left: 10px;">@ Abrechnung</button>
                `;
        if (angemeldetesMitglied.cc_admin == true) {
            html += `<button class="kleinerBt" onclick="MitgliederAusweise('${kunde.schlüssel}')" style="margin-left: 10px;">Bezahlkarte</button>
            <button class="kleinerBt" onclick="TankstellenQRCode('${kunde.schlüssel}')" style="margin-left: 10px;">QR-Code</button>
            <button class="kleinerBt" style="width: auto;" onclick="KontoAusgleichen('${kunde.uid}', ${kunde.Kontostand})" style="margin-left: 10px;">Konto ausgleichen</button>
            <button class="kleinerBt" style="width: auto;" onclick="OnlineBuchung('${kunde.uid}')" style="margin-left: 10px;">Buchung hinzufügen</button>`
            ;
        }

        html += `
            <table style="border-spacing: 10px;">
                <tr>
                    <td><b>Name</b></td>
                    <td>${kunde.firstname} ${kunde.lastname}</td>
                </tr>
                <tr>
                    <td><b>ID</b></td>
                    <td>${kunde.uid}</td>
                </tr>
                <tr>
                    <td><b>Mitgliedsnr</b></td>
                    <td>${kunde.memberid ? kunde.memberid : 'ohne'}</td>
                </tr>
                <tr>
                    <td><b>Email</b></td>
                    <td>${kunde.email}</td>
                </tr> 
                <tr>
                    <td><b>Schlüssel</b></td>
                    <td>
                        ${kunde.schlüssel}
                        
                    </td>
                </tr>
                <tr>
                    <td><b>Rollen</b></td>
                    <td>${kunde.cc_admin ? "<mark>Kassenwart</mark>" : ""} ${kunde.cc_seller ? "<mark>Verkäufer</mark>" : ""} ${kunde.cc_member ? "<mark>Mitglied</mark>" : ""} ${kunde.cc_guest ? "<mark>Gast</mark>" : ""}</td>
                </tr>
                <tr>
                    <td><b>Kontostand</b></td>
                    <td>-${kunde.Kontostand ? kunde.Kontostand : 0} € </td>
                </tr>
                <tr>
                    <td><b>Beziehung</b></td>
                    <td>
                        ${beziehungstext}
                    </td>
                </tr>
                <tr>
            </table>

            <hr>

            <h2 style="display: inline;"><a id="TabellenLink1" style='text-decoration: none;' href='#' onclick='toggleTabelle("Tabelle1", "TabellenLink1")'>➡️</a> Umsätze</h2>
            <table id="Tabelle1" class="portal-table" style="display: none; margin-top: 20px;">
                <tr>
                    <th>T</th>
                    <th>Datum</th>
                    <th>Zeit</th>
                    <th class="links">Produkt</th>
                    <th class="links">Kategorie</th>
                    <th class="rechts">Preis</th>
                    <th></th>
                </tr>
            
        <tbody>`;

        KäufeFilter.forEach((verkauf, index) => {    
            html += `<tr>
                <td>${verkauf.Terminal}</td>
                <td>${verkauf.Datum}</td>
                <td>${verkauf.Zeit}</td>
                <td class="links">${verkauf.Produkt}</td>
                <td class="links">${verkauf.Kategorie}</td>
                <td class="rechts">${verkauf.Preis} €</td>
            `;
            
            if (angemeldetesMitglied.cc_admin === true) {
                html += `<td><a href="#" onclick="deleteVerkauf(${verkauf.originalIndex})">🗑️</a></td>`;
            }
            
            html += `</tr>`;
            summe += parseFloat(verkauf.Preis);
        });
        html += `
            <tr style="border-top: 1px solid black;">
                <td colspan="5" class="rechts"><b>Summe</b></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>
    
        </tbody>    
            </table>`;


        // Übersicht nach Produkten
        html += `
            <hr>
            <h2 style="display: inline;"><a id="TabellenLink2" style='text-decoration: none;' href='#' onclick='toggleTabelle("Tabelle2", "TabellenLink2")'>➡️</a> Übersicht nach Produkten</h2>
           <table id="Tabelle2" class="portal-table" style="display: none; margin-top: 20px;">
                <tr>
                    <th>Anzahl</th>
                    <th class="links">Produkt</th>
                    <th class="links">Kategorie</th>
                    <th class="rechts">Einzelpreis</th> 
                    <th class="rechts">Gesamtpreis</th>
                </tr>
            <tbody>`;

        let produktsumme = 0;
        let produktanzahl = 0;
        summe = 0;

        produkte.forEach(produkt => {
            produktsumme = 0;
            produktanzahl = 0;
            let html2 = ""; // HTML für die Produktübersicht
            KäufeFilter.forEach(verkauf => {
                if (verkauf.EAN === produkt.EAN) {
                    if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                        produktsumme += parseFloat(verkauf.Preis);
                    }
                    produktanzahl++;
                }
            });
            
            if (produktanzahl === 0) return; // Wenn keine Verkäufe für dieses Produkt, überspringen
            
            html += `
                <tr>
                    <td>${produktanzahl}</td>
                    <td class="links">${produkt.Bezeichnung}</td>
                    <td class="links">${produkt.Kategorie}</td>
                    <td class="rechts">${produkt.Preis} €</td>
                    <td class="rechts">${produktsumme.toFixed(2)} €</td>
                </tr>`;
                summe += produktsumme;
        });
        html += `
            <tr style="border-top: 1px solid black;">
                <td colspan="4" class="rechts"><b>Summe</b></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>   
        </tbody></table>`;

        // Übersicht nach Produktengrupen
        html += `    
            <hr>
            <h2 style="display: inline;"><a id="TabellenLink3" style='text-decoration: none;' href='#' onclick='toggleTabelle("Tabelle3", "TabellenLink3")'>➡️</a> Übersicht nach Produktgruppen</h2>
            <table id="Tabelle3" class="portal-table" style="display: none; margin-top: 20px;">
                <tr>
                    <th>Anzahl</th>
                    <th class="links">Produktgruppe</th>
                    <th class="rechts">Summe</th>
                </tr>
            <tbody>`;

        let gruppensumme = 0;
        let gruppenanzahl = 0;
        summe = 0;

        let produktgruppen = [...new Set(produkte.map(produkt => produkt.Kategorie))]; // Einzigartige Produktgruppen extrahieren
        produktgruppen.forEach(gruppe => {
            gruppensumme = 0;
            gruppenanzahl = 0;
            KäufeFilter.forEach(verkauf => {
                if (verkauf.Kategorie === gruppe) {
                    if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                        gruppensumme += parseFloat(verkauf.Preis);
                    }
                    gruppenanzahl++;
                }
            });
            
            if (gruppenanzahl === 0) return; // Wenn keine Verkäufe für diese Produktgruppe, überspringen
            
            html += `
                <tr>
                    <td>${gruppenanzahl}</td>
                    <td class="links">${gruppe}</td>
                    <td class="rechts">${gruppensumme.toFixed(2)} €</td>
                </tr>`;
                summe += gruppensumme;
        });


        html += `
            <tr style="border-top: 1px solid black;">
                <td colspan="2" class="rechts"><b>Summe</b></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>
        </tbody></table>`;
    

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

        const btn = document.getElementById("bt_aktualisierung");
        btn.addEventListener("click", () => {
            const datumA = document.getElementById("datum_anfang").value;
            const datumE = document.getElementById("datum_ende").value;
            Kundenübersicht(kundennummer,new Date(datumA), new Date(datumE));
        });
    }

    /**
     * Lädt Mitgliederdaten aus Vereinsflieger API
     * Aktualisiert lokale Kundendatenbank mit aktuellen Vereinsdaten
     */
    function Mitgliedsdaten_ziehen() {
        portalmenu2.innerHTML = "<h2 style='display: inline;'>Vereinsflieger Datenimport</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, die Mitgliederdaten werden aus Vereinsflieger abgerufen...</p>";
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "pull_Mitgliedsdaten_Vereinsflieger.php", true); 
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                portalInhalt.innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }

    /**
     * Zeigt Liste aller gespeicherten Backup-Dateien
     * Lädt Dateiliste von get-backup-files.php
     */
    function backupliste() {
        portalmenu2.innerHTML = "<h2 style='display: inline;'>gespeicherte Backups</h2>";
        fetch('get-backup-files.php')
        .then(response => response.text())
        .then(data => {
            portalInhalt.innerHTML = data;
        })
        .catch(error => console.error('Fehler beim Laden der Dateien:', error));
    }

    /**
     * Führt Mitglieder und externe Kunden zu einer gemeinsamen Liste zusammen
     * Externe Kunden werden als Gäste mit angepassten Eigenschaften hinzugefügt
     * @returns {Array} Kombiniertes Array aus Mitgliedern und externen Kunden
     */
    function MitgliederExterneZusammenführen() {

        let käufer = [...kunden];

        if (!externe) return käufer;

        //Kopie der externen Kunden mit angepassten Eigenschaften
        let externeTemp = externe.map(externer => ({
            ...externer,
            uid: externer.schlüssel,
            cc_admin: false,
            cc_guest: true,
            cc_member: false,
            cc_seller: false
        }));

        // käufer Array um externe Kunden erweitern
        return [...käufer, ...externeTemp];
    }

    /**
     * Zeigt alle Verkäufe des aktuellen Tages
     * Listet Datum, Zeit, Produkt, Kategorie, Preis und Gesamtsumme
     */
    function Tagesumsätze() {

        let datum1 = heute; // Aktuelles Datum im Format YYYY-MM-DD
        let summe = 0;
        let menu2 = "";
        menu2 += `
            <h2 style="display: inline;">Tagesumsätze - ${datum1.toISOString().split('T')[0]}</h2>
        `;

        let html = "";
        html += `
        <table class="portal-table">
            <tr>
                <th>T</th>
                <th>Zeit</th>
                <th class="links">Kunde</th>
                <th class="links">Produkt</th>
                <th class="rechts">Preis</th>
            </tr>
        <tbody>`;

        verkäufe.forEach(verkauf => {
    
            if (verkauf.Datum === datum1.toISOString().split('T')[0]) {

                Kunde = käufer.find(kunde => kunde.uid === verkauf.Kundennummer);

                console.log("Kunde: " + JSON.stringify(Kunde));
                
                if (!Kunde) {
                    console.warn(`Kunde mit UID ${verkauf.Kundennummer} nicht gefunden.`);
                    Kunde = {
                        lastname: "GELÖSCHT", // Fallback für unbekannte Kunden
                        firstname: ""  // Fallback für unbekannte Kunden
                    };
                }

                html += `
                    <tr>
                        <td>${verkauf.Terminal}</td>
                        <td>${verkauf.Zeit}</td>
                        <td class="links">${Kunde.lastname}, ${Kunde.firstname}</td>
                        <td class="links">${verkauf.Produkt}</td>
                        <td class="rechts">${verkauf.Preis} €</td>
                    </tr>`;
                    if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                        summe += parseFloat(verkauf.Preis);
                    }
            }
        }); 

        html += `
            <tr style="border-top: 1px solid black;">
                <td colspan="4" class="rechts"><b>Summe</b></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>
        </tbody></table>`;

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;
  
    }

    /**
     * Zeigt Umsätze für einen wählbaren Zeitraum
     * Gruppiert nach: Einzelumsätze, Produkte, Produktgruppen
     * @param {Date} datum1 - Startdatum (optional, Standard: Jahresbeginn)
     * @param {Date} datum2 - Enddatum (optional, Standard: Heute)
     */
    function Umsätze(datum1, datum2) {

        // Wenn kein Datum angegeben ist, setze es auf den 1. Januar des aktuellen Jahres
        // und das aktuelle Datum
        if(!datum1 || !datum2) {
            let datumjahr = heute.getFullYear();
            datum1 = jahresbeginn
            datum2 = heute; // Aktuelles Datum
        } 

        let summe = 0;
        let nettosumme = 0;
        let menu2 = "";
        let html = "";
        menu2 += `
            <h2 style="display: inline;">Umsätze</h2>
            <input class="DatumInput" type="date" id="datum_anfang" value="${datum1.toISOString().split('T')[0]}">
            <h2 style="display: inline;"> bis </h2>
            <input class="DatumInput" type="date" id="datum_ende" value="${datum2.toISOString().split('T')[0]}">
            <button class="kleinerBt" id="bt_aktualisierung">aktualisieren</button>
            <button class="kleinerBt" onclick="Umsätze(monatsbeginn, heute)">Monat</button>
            <button class="kleinerBt" onclick="Umsätze(wochenbeginn, heute)">Woche</button>
            <button class="kleinerBt" onclick="Umsätze(heute, heute)">Tag</button>
        `;

        //Tabelle1 - Einzelumsätze
            html += `
  
            <h2 style="display: inline;"><a id="TabellenLink1" style='text-decoration: none;' href='#' onclick='toggleTabelle("Tabelle1", "TabellenLink1")'>➡️</a> Einzelumsätze</h2>
            <table id="Tabelle1" class="portal-table" style="display: none; margin-top: 20px;">
                <tr>
                    <th>T</th>
                    <th>Datum</th>
                    <th>Zeit</th>
                    <th class="links">Kunde</th>
                    <th class="links">Produkt</th>
                    <th class="rechts">Netto</th>
                    <th class="rechts">MwSt</th>
                    <th class="rechts">Brutto</th>
                    <th></th>
                </tr>

            <tbody>`;

            verkäufe.forEach((verkauf, index) => {
            
                if (verkauf.Datum >= datum1.toISOString().split('T')[0] && verkauf.Datum <= datum2.toISOString().split('T')[0]) {

                    Kunde = käufer.find(kunde => kunde.uid === verkauf.Kundennummer);

                    let KNummer = String(verkauf.Kundennummer);

                    if (!Kunde) {
                        console.warn(`Kunde mit UID ${verkauf.Kundennummer} nicht gefunden.`);
                        Kunde = {
                            lastname: "GELÖSCHT", // Fallback für unbekannte Kunden
                            firstname: KNummer  // Fallback für unbekannte Kunden
                        };
                    }

                    html += `
                        <tr>
                            <td>${verkauf.Terminal}</td>
                            <td>${verkauf.Datum}</td>
                            <td>${verkauf.Zeit}</td>
                            <td class="links">${Kunde.lastname}, ${Kunde.firstname}</td>
                            <td class="links">${verkauf.Produkt}</td>
                            <td class="rechts">${(verkauf.Preis/(1 + verkauf.MwSt/100)).toFixed(2)} €</td>
                            <td class="rechts">${verkauf.MwSt} %</td>
                            <td class="rechts">${verkauf.Preis} €</td>
                            <td><a href="#" onclick="deleteVerkauf(${index})">🗑️</a></td>
                        </tr>`;
                        if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                            summe += parseFloat(verkauf.Preis);
                            nettosumme += parseFloat(verkauf.Preis/(1 + verkauf.MwSt/100));
                        }
                }
            }); 

            html += `
                <tr class="summenzeile">
                    <td colspan="5" class="rechts"><b>Summe</b></td>
                    <td class="rechts"><b>${nettosumme.toFixed(2)} €</b></td>
                    <td class="rechts"><b>${(summe - nettosumme).toFixed(2)} €</b></td>
                    <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
                </tr>
                <tr>
                    <td colspan="9" style="text-align: left;">Die Mehrwehrsteuersätze richten sich nach den Angaben des Mehrwehrtsteuersatzes zum Zeitpunkt des Verkaufs. Abweichungen zu den Gesamtsumsätzen können daher auftreten, wenn sich die MwSt Sätze geändert haben oder falsch hinterlegt sind.</td>
                </tr>
            </tbody></table>`;

        //Tabelle2 - Übersicht nach Produkten
            html += `<hr><h2 style="display: inline;"><a id="TabellenLink2" style='text-decoration: none;' href='#' onclick='toggleTabelle("Tabelle2", "TabellenLink2")'>➡️</a> Übersicht nach Produkten</h2>
                <table id="Tabelle2" class="portal-table" style="display: none; margin-top: 20px;">
                    <tr>
                        <th></th>
                        <th>Anzahl</th>
                        <th class="links">Produkt</th>
                        <th class="links">Kategorie</th>
                        <th class="rechts">Nettoeinzelpreis</th> 
                        <th class="rechts">MwSt</th>
                        <th class="rechts">Bruttoeinzelpreis</th>
                        <th class="rechts">Nettosumme</th>
                        <th class="rechts">Bruttosumme</th>
                    </tr>
                <tbody>
            `;

            summe = 0;
            bruttosumme = 0;

            produkte.forEach(produkt => {
                produktsumme = 0;
                produktanzahl = 0;
                produktbruttosumme = 0;
                
                let html2 = ""; // HTML für die Produktübersicht
                verkäufe.forEach(verkauf => {
                    if (verkauf.EAN === produkt.EAN && verkauf.Datum >= datum1.toISOString().split('T')[0] && verkauf.Datum <= datum2.toISOString().split('T')[0]) {
                        if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                            produktsumme += parseFloat(verkauf.Preis);
                            produktbruttosumme += parseFloat(verkauf.Preis/(1 + verkauf.MwSt/100));
                        }
                        produktanzahl++;
                    }
                });
                
                if (produktanzahl === 0) return; // Wenn keine Verkäufe für dieses Produkt, überspringen
         
                html += `
                    <tr>
                        <td><a id="TabellenLink_${produkt.EAN}" style='text-decoration: none;' href='#' onclick='toggleTabelle2("zusatzspalte_${produkt.EAN}", "TabellenLink_${produkt.EAN}")'>➡️</a></td>
                        <td>${produktanzahl}</td>
                        <td class="links">${produkt.Bezeichnung}</td>
                        <td class="links">${produkt.Kategorie}</td>
                        <td class="rechts">${(produkt.Preis/(1 + produkt.MwSt/100)).toFixed(2)} €</td>
                        <td class="rechts">${produkt.MwSt} %</td>
                        <td class="rechts">${produkt.Preis} €</td>
                        <td class="rechts">${(produktsumme/(1 + produkt.MwSt/100)).toFixed(2)} €</td>
                        <td class="rechts">${produktsumme.toFixed(2)} €</td>
                    </tr>
                `;

                //sollte es sich um Treibstoff handeln, wird eine besondereDetailansicht mit den Zählerständen und Verbräuchen angezeigt
                if (produkt.Zählerstand>0) { 
                    let Zählerstand_alt = 0;
                    let Zählerstand_neu = 0;    
                    let Leerlauf = 0;
                    let Verbrauch = 0;
                    let Leerlauf_summe = 0;
                    let Verbrauch_summe = 0;
                    let brutto_summe = 0;
                    let netto_summe = 0;
                    let Zählerstand_kontrolle = 0;

                    html += `
                        <tr id="zusatzspalte_${produkt.EAN}" style="display: none;">
                            <td colspan="9">
                                <table class="portal-table" style="margin-top: 0">
                                    <tr>
                                        <th>T</th>
                                        <th>Datum</th>
                                        <th>Zeit</th>
                                        <th class="links">Kunde</th>
                                        <th class="rechts">Zählerstand_alt</th>
                                        <th class="rechts">Zählerstand_neu</th>
                                        <th class="rechts">Leerlauf</th>
                                        <th class="rechts">Verbrauch</th>
                                        <th class="rechts">Netto</th>
                                        <th class="rechts">MwSt</th>
                                        <th class="rechts">Brutto</th>
                                    </tr>
                                    <tbody>`;
                                    verkäufe.forEach(verkauf => {
                                        if (verkauf.EAN === produkt.EAN && verkauf.Datum >= datum1.toISOString().split('T')[0] && verkauf.Datum <= datum2.toISOString().split('T')[0]) {
                    
                                            Kunde = käufer.find(kunde => kunde.uid === verkauf.Kundennummer);
                    
                                            let KNummer = String(verkauf.Kundennummer);
                    
                                            if (!Kunde) {
                                                console.warn(`Kunde mit UID ${verkauf.Kundennummer} nicht gefunden.`);
                                                Kunde = {
                                                    lastname: "GELÖSCHT", // Fallback für unbekannte Kunden
                                                    firstname: KNummer  // Fallback für unbekannte Kunden
                                                };
                                            }

                                            // Zählerstände und Verbrauch auslesen und berechnen
                                            Zählerstand_alt = verkauf.Produkt || 0;

                                            //Lese verkauf.Produkt den ersten Wert hintet dem 1. # bis zur ersten Leerstelle als Zählerstand_alt enthält
                                            if (verkauf.Produkt.includes("#")) { // Auftrag: prüfen, ob if überhaupt nötig ist!
                                                Zählerstand_alt = verkauf.Produkt.split("#")[1].split(" ")[0] || 0;
                                            }
                                            //Lese verkauf.Produkt den ersten Wert hinter dem 2. # bis zur ersten Leerstelle als Zählerstand_neu enthält
                                            Zählerstand_neu = verkauf.Produkt.split("#")[2]?.split(" ")[0] || 0;

                                            //Lese verkauf.Produkt den ersten Wert hinter dem 3. # bis zur ersten Leerstelle als Leerlauf enthält
                                            Leerlauf = verkauf.Produkt.split("#")[3]?.split(" ")[0] || 0;
                                            Leerlauf_summe += parseFloat(Leerlauf);
                                            
                                            //Berechne Verbrauch als Differenz zwischen Zählerstand_neu und Zählerstand_alt minus Leerlauf
                                            Verbrauch = Zählerstand_neu - Zählerstand_alt - Leerlauf || 0; 
                                            Verbrauch_summe += parseFloat(Verbrauch);   
                                            
                                            netto_summe += parseFloat(verkauf.Preis/(1 + verkauf.MwSt/100));
                                            brutto_summe += parseFloat(verkauf.Preis);
                    
                                            html += `
                                                <tr>
                                                    <td>${verkauf.Terminal}</td>
                                                    <td>${verkauf.Datum}</td>
                                                    <td>${verkauf.Zeit}</td>
                                                    <td class="links">${Kunde.lastname}, ${Kunde.firstname}</td>
                                                    <td class="rechts">`


                                            console.log("Zählerstand_kontrolle: " + Zählerstand_kontrolle);        
                                            if (Zählerstand_kontrolle == Zählerstand_alt||Zählerstand_kontrolle == 0) {
                                                html += `${Zählerstand_alt} l</td>`;
                                            } else {
                                                html += `<mark>${Zählerstand_alt} l</mark></td>`;
                                            }

                                            Zählerstand_kontrolle = Zählerstand_neu;

                                            html += `
                                                    <td class="rechts">${Zählerstand_neu} l</td>
                                                    <td class="rechts">${Leerlauf} l</td>
                                                    <td class="rechts">${Verbrauch.toFixed(2)} l</td> 
                                                    <td class="rechts">${(verkauf.Preis/(1 + verkauf.MwSt/100)).toFixed(2)} €</td>
                                                    <td class="rechts">${verkauf.MwSt} %</td>
                                                    <td class="rechts">${verkauf.Preis} €</td>
                                                </tr>`; 
                                        }
                                    }); 

                                    html += `
                                        <tr class="summenzeile">
                                            <td colspan="6" class="rechts"><b>Summe</b></td>
                                            <td class="rechts"><b>${Leerlauf_summe.toFixed(2)} l</b></td>
                                            <td class="rechts"><b>${Verbrauch_summe.toFixed(2)} l</b></td>
                                            <td class="rechts"><b>${netto_summe.toFixed(2)} €</b></td>
                                            <td></td>
                                            <td class="rechts"><b>${brutto_summe.toFixed(2)} €</b></td>
                                        </tr>
                                    </tbody>
                                    </table>
                            </td>
                        </tr>`;
                }
                
                // Sollte es sich nicht um Treibstoff handeln, wird eine normale Detailansicht mit den Einzelumsätzen angezeigt
                else  {
                    html += `<tr id="zusatzspalte_${produkt.EAN}" style="display: none;">
                        <td colspan="9">
                            <table class="portal-table" style="margin-top: 0">
                                <tr>
                                    <th>T</th>
                                    <th>Datum</th>
                                    <th>Zeit</th>
                                    <th class="links">Kunde</th>
                                    <th class="rechts">Netto</th>
                                    <th class="rechts">MwSt</th>
                                    <th class="rechts">Brutto</th>
                                </tr>
                            <tbody>`;

                            verkäufe.forEach(verkauf => {
                                if (verkauf.EAN === produkt.EAN && verkauf.Datum >= datum1.toISOString().split('T')[0] && verkauf.Datum <= datum2.toISOString().split('T')[0]) {
    
                                    Kunde = käufer.find(kunde => kunde.uid === verkauf.Kundennummer);
    
                                    let KNummer = String(verkauf.Kundennummer);
    
                                    if (!Kunde) {
                                        console.warn(`Kunde mit UID ${verkauf.Kundennummer} nicht gefunden.`);
                                        Kunde = {
                                            lastname: "GELÖSCHT", // Fallback für unbekannte Kunden
                                            firstname: KNummer  // Fallback für unbekannte Kunden
                                        };
                                    }
    
                                    html += `
                                        <tr>
                                            <td>${verkauf.Terminal}</td>
                                            <td>${verkauf.Datum}</td>
                                            <td>${verkauf.Zeit}</td>
                                            <td class="links">${Kunde.lastname}, ${Kunde.firstname}</td>
                                            <td class="rechts">${(verkauf.Preis/(1 + verkauf.MwSt/100)).toFixed(2)} €</td>
                                            <td class="rechts">${verkauf.MwSt} %</td>
                                            <td class="rechts">${verkauf.Preis} €</td>
                                        </tr>`; 
                                }
                            }); 
                            html += `
                            </tbody>    
                            </table>
                            
                        
                        </td>    
                    </tr>`;
                summe += produktsumme;
                bruttosumme += produktbruttosumme;
                }
            });
            html += `
                <tr class="summenzeile">
                    <td colspan="4" class="rechts"><b>Summe</b></td>
                    <td></td>
                    <td class="rechts"><b>${(summe-bruttosumme).toFixed(2)} €</b></td>
                    <td></td>
                    <td class="rechts"><b>${bruttosumme.toFixed(2)} €</b></td>
                      
                    <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
                </tr>
                <tr>
                    <td colspan="8" style="text-align: left;">Die Mehrwehrsteuersätze richten sich nach den aktuellen Angaben im Produktstamm. Abweichungen zu den Einzelumsätzen können daher auftreten, wenn sich die MwSt Sätze geändert haben oder falsch hinterlegt sind.</td>
                </tr>
                </table>
                
                `;

        //Tabelle3 - Übersicht nach Produktengruppen
            html += `<hr><h2 style="display: inline;"><a id="TabellenLink3" style='text-decoration: none;' href='#' onclick='toggleTabelle("Tabelle3", "TabellenLink3")'>➡️</a> Übersicht nach Produktgruppen</h2>
                <table id="Tabelle3" class="portal-table" style="display: none; margin-top: 20px;">
                    <tr>
                        <th>Anzahl</th>
                        <th class="links">Produktgruppe</th>
                        <th class="rechts">Summe</th>
                    </tr>
                <tbody>
            `;

            summe = 0;
            let gruppensumme = 0;
            let gruppenanzahl = 0;
            let produktgruppen = [...new Set(produkte.map(produkt => produkt.Kategorie))]; // Einzigartige Produktgruppen extrahieren

            produktgruppen.forEach(gruppe => {
                gruppensumme = 0;
                gruppenanzahl = 0;
                
                verkäufe.forEach(verkauf => {
                    if (verkauf.Kategorie === gruppe && verkauf.Datum >= datum1.toISOString().split('T')[0] && verkauf.Datum <= datum2.toISOString().split('T')[0]) {
                        if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                            gruppensumme += parseFloat(verkauf.Preis);
                        }
                        gruppenanzahl++;
                    }
                });

                html += `
                    <tr>
                        <td>${gruppenanzahl}</td>
                        <td class="links">${gruppe}</td>
                        <td class="rechts">${gruppensumme.toFixed(2)} €</td>
                    </tr>`;
                
                summe += gruppensumme;

            });
            html += `
                <tr class="summenzeile">
                    <td colspan="2" class="rechts"><b>Summe</b></td>
                    <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
                </tr>      
            </tbody>

            </table>`;

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

        const btn = document.getElementById("bt_aktualisierung");
        btn.addEventListener("click", () => {
            const datumA = document.getElementById("datum_anfang").value;
            const datumE = document.getElementById("datum_ende").value;

            Umsätze(new Date(datumA), new Date(datumE));
        });
    }

    /**
     * Erstellt Tageszusammenfassung mit Statistiken
     * Zeigt Verkaufszahlen nach Produkten und Kategorien
     */
    function Tageszusammenfassung() { 

        let datum1 = heute; // Aktuelles Datum im Format YYYY-MM-DD
        let summe = 0;
        let produktsumme = 0;   
        let html = "";

        let menu2 = "";
        menu2 += `
            <h2 style="display: inline;">Tageszusammenfassung - ${datum1.toISOString().split('T')[0]}</h2>
        `;

        html += `
        <table class="portal-table">
            <tr>
                <th>Anzahl</th>
                <th class="links">Produkt</th>
                <th class="rechts">Einzelpreis</th> 
                <th class="rechts">Gesamtpreis</th>
            </tr>
        <tbody>`;

        const VerkäufeDatumFilter = verkäufe.filter(auswahl => auswahl.Datum === datum1.toISOString().split('T')[0]);

        const zusammenfassung = {};

        VerkäufeDatumFilter.forEach(eintrag => {
            const ean = eintrag.EAN;
            const preis = parseFloat(eintrag.Preis);

            if (!zusammenfassung[ean]) {
                zusammenfassung[ean] = {
                    produkt: eintrag.Produkt,
                    einzelpreis: preis,
                    anzahl: 0,
                    gesamtpreis: 0
                };
            }

            zusammenfassung[ean].anzahl += 1;
            zusammenfassung[ean].gesamtpreis += preis;
        });

        for (const [ean, ds] of Object.entries(zusammenfassung)) {
            
            html +=  `
                <tr>
                    <td>${ds.anzahl}</td>
                    <td class="links">${ds.produkt}</td>
                    <td class="rechts">${ds.einzelpreis.toFixed(2)} €</td>
                    <td class="rechts">${ds.gesamtpreis.toFixed(2)} €</td>
                </tr>`;
                
                summe += ds.gesamtpreis;}


        html += `
            <tr style="border-top: 1px solid black;">
                <td colspan="3" class="rechts"><b>Summe</b></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>
        </tbody></table>`;
        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;
    }
    
    /**
     * Berechnet Kontostände aller Kunden aus Verkaufsdaten
     * Gruppiert nach Kundennummer und Kategorie
     * @param {Array} daten - Array mit Verkaufsdaten
     * @returns {Array} Array mit Kundennummer, Summe und Kategorien
     */
    function Kundenkontostand(daten) {
        const kundenDaten = daten.reduce((acc, eintrag) => {
            const { Kundennummer, Kategorie, Produkt, Preis } = eintrag;
            const preis = parseFloat(Preis);

            if (!acc[Kundennummer]) {
            acc[Kundennummer] = { Summe: 0, Kategorien: {} };
            }
            const kunde = acc[Kundennummer];
            kunde.Summe += preis;

            if (!kunde.Kategorien[Kategorie]) {
            kunde.Kategorien[Kategorie] = { Name: Kategorie, Anzahl: 0, Summe: 0, Produkte: {} };
            }
            const kat = kunde.Kategorien[Kategorie];
            kat.Anzahl += 1;
            kat.Summe += preis;

            if (!kat.Produkte[Produkt]) {
            kat.Produkte[Produkt] = { Name: Produkt, Anzahl: 0, Summe: 0 };
            }
            const prod = kat.Produkte[Produkt];
            prod.Anzahl += 1;
            prod.Summe += preis;

            return acc;
        }, {});

        return Object.entries(kundenDaten).map(([Kundennummer, kunde]) => ({
            Kundennummer,
            Summe: kunde.Summe.toFixed(2),
            Kategorien: Object.values(kunde.Kategorien).map(kategorie => ({
            Name: kategorie.Name,
            Anzahl: kategorie.Anzahl,
            Summe: kategorie.Summe.toFixed(2),
            Produkte: Object.values(kategorie.Produkte).map(produkt => ({
                Name: produkt.Name,
                Anzahl: produkt.Anzahl,
                Summe: produkt.Summe.toFixed(2)
            }))
            }))
        }));
    }

    /**
     * Zeigt/Versteckt Tabellen und ändert Pfeil-Icon
     * @param {string} tabelleId - ID der zu toggelnden Tabelle
     * @param {string} linkId - ID des Link-Elements mit Pfeil
     */
    function toggleTabelle(tabelleId, linkId) {
        var tabelle = document.getElementById(tabelleId);
        var link = document.getElementById(linkId);
        
        // Wenn die Tabelle ausgeblendet ist, zeige sie an und ändere das Symbol
        if (tabelle.style.display === 'none' || tabelle.style.display === '') {
            tabelle.style.display = 'table';
            link.textContent = '⬇️';  // Symbol ändern (z.B. nach unten)
        } 
        // Wenn die Tabelle angezeigt wird, verstecke sie und ändere das Symbol
        else {
            tabelle.style.display = 'none';
            link.textContent = '➡️';  // Symbol ändern (z.B. nach rechts)
        }
    }

    function toggleTabelle2(tabelleId, linkId) {
        var tabelle = document.getElementById(tabelleId);
        var link = document.getElementById(linkId);
        
        // Wenn die Tabelle ausgeblendet ist, zeige sie an und ändere das Symbol
        if (tabelle.style.display === 'none' || tabelle.style.display === '') {
            tabelle.style.display = 'contents';
            link.textContent = '⬇️';  // Symbol ändern (z.B. nach unten)
        } 
        // Wenn die Tabelle angezeigt wird, verstecke sie und ändere das Symbol
        else {
            tabelle.style.display = 'none';
            link.textContent = '➡️';  // Symbol ändern (z.B. nach rechts)
        }
    }

    /**
     * Erstellt Abrechnungsübersicht mit MwSt-Aufschlüsselung
     * Gruppiert Umsätze nach Kunden und Steuersätzen
     * @param {Date} datum1 - Startdatum (optional)
     * @param {Date} datum2 - Enddatum (optional)
     */
    function Abrechnung(datum1, datum2) {
        // Default dates if none provided (full year)
        if(!datum1 || !datum2) {
            datum1 = jahresbeginn; // From global variable
            datum2 = heute; // From global variable
        }

        // Array für die Abrechnung erstellen
        let abrechnung = [];
        
        // Über alle Kunden iterieren
        kunden.forEach(kunde => {
            // Summe aller Verkäufe für diesen Kunden im gewählten Zeitraum
            const kundenUmsatz = verkäufe
                .filter(verkauf => 
                    verkauf.Kundennummer === kunde.uid &&
                    verkauf.Datum >= datum1.toISOString().split('T')[0] && 
                    verkauf.Datum <= datum2.toISOString().split('T')[0]
                )
                .reduce((acc, verkauf) => {
                    return {
                        Anzahl: acc.Anzahl + 1,
                        Summe: acc.Summe + parseFloat(verkauf.Preis)
                    };
                }, {Anzahl: 0, Summe: 0});

            // Nur Kunden mit Umsatz hinzufügen
            if (kundenUmsatz.Anzahl > 0) {
                
                let gutschrift =  -kundenUmsatz.Summe

                abrechnung.push({
                    bookingdate: datum2.toISOString().split('T')[0],
                    Zeit: datum2.toISOString().split('T')[1].substring(0, 5), // Nur Stunden und Minuten
                    Terminal: "Z",
                    Schlüssel: kunde.schlüssel,
                    Uid: kunde.uid,
                    EAN: 9999, // Dummy EAN
                    Produkt: "Kontoausgleich-VF",
                    Art: "Buchung",
                    Gutschrift: gutschrift.toFixed(2),
                    Steuern: 0, // Keine Steuern für Kontoausgleich
                    articleid: "1017",
                    memberid: parseInt(kunde.memberid, 10),
                    amount: kundenUmsatz.Anzahl,
                    callsign: "ClubCash",
                    saletax: 19,
                    comment: datum1.toISOString().split('T')[0] + " bis " + datum2.toISOString().split('T')[0] + " - " + kunde.lastname + ", " + kunde.firstname,
                    spid: 4,
                    totalprice: kundenUmsatz.Summe.toFixed(2)
                });
            }
        });

        // Tabelle erstellen und anzeigen
        let html2 = `
            <h2 style='display: inline;'>Abrechnung - Mitglieder</h2>
            <div class="datumauswahl">
                <input class="DatumInput" type="date" id="datum_anfang" value="${datum1.toISOString().split('T')[0]}">
                <h2 style="display: inline;"> bis </h2>
                <input class="DatumInput" type="date" id="datum_ende" value="${datum2.toISOString().split('T')[0]}">
                <button id="bt_aktualisierung" class="kleinerBt">aktualisieren</button>
                <button class="kleinerBt" onclick="Abrechnung(monatsbeginn, heute)">Monat</button>
                <button class="kleinerBt" onclick="Abrechnung(wochenbeginn, heute)">Woche</button>
                <button class="kleinerBt" onclick="Abrechnung(heute, heute)">Tag</button>
                <button id="bt-abrechnungExport" class="kleinerBt">Export an VF</button>
                <button id="bt-emailAlle" class="kleinerBt">Email an alle</button>
            </div> `;

            let html = `
            <table class="portal-table">
                <tr>
                    <th>Datum</th>
                    <th>Mitgliedsnr</th>
                    <th>Buchungen</th>
                    <th>Buchungstext</th>
                    <th>Gesamtpreis</th>
                </tr>
        `;

        let gesamtSumme = 0;
        let gesamtAnzahl = 0;

        abrechnung.forEach(eintrag => {
            gesamtSumme += parseFloat(eintrag.Summe);
            gesamtAnzahl += eintrag.Anzahl;

            html += `
                <tr>
                    <td>${eintrag.bookingdate}</td>
                    <td>${eintrag.memberid}</td>
                    <td>${eintrag.amount}</td>
                    <td class="links">${eintrag.comment}</td>
                    <td class="rechts">${eintrag.totalprice} €</td>
                </tr>
            `;
        });
        html += "</table>";

        // Anzeige im Portal
        portalmenu2.innerHTML = html2;
        portalInhalt.innerHTML = html;

        // Event Listener für Datumsauswahl
        const btn = document.getElementById("bt_aktualisierung");
        if(btn) {
            btn.addEventListener("click", () => {
                const datumA = document.getElementById("datum_anfang").value;
                const datumE = document.getElementById("datum_ende").value;
                Abrechnung(new Date(datumA), new Date(datumE));
            });
        }
        
        // Event Listener für Export-Button
        const exportBtn = document.getElementById("bt-abrechnungExport");
        exportBtn.addEventListener("click", () => {
                abrechnungExport(abrechnung);
        });

        // Event Listener für Email an alle Button
        const emailAlleBtn = document.getElementById("bt-emailAlle");
        if(emailAlleBtn) {
            emailAlleBtn.addEventListener("click", () => {
                Emailabrechnung_alle(datum1, datum2);
            });
        }
    }

    /**
     * Exportiert Abrechnungsdaten als JSON-Datei
     * Startet automatischen Download im Browser
     * @param {Object} abrechnungsdaten - Zu exportierende Abrechnungsdaten
     */
    function abrechnungExport(abrechnungsdaten) {

        portalmenu2.innerHTML = "<h2 style='display: inline;'>Export der Abrechnung an Vereinsflieger</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, die Abrechnung wird an Vereinsflieger übertragen.<br>Der Vorgang kann länger dauern.<br>Bitte den Vorgang nicht abbrechen...</p>";
        document.getElementById("preloader").style.display = "block";

        // Array in JSON konvertieren und an PHP-Datei senden
        fetch('push_Verkaufsdaten_Vereinsflieger.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(abrechnungsdaten)
        })
        .then(response => response.text())
        .then(result => {
            console.log('Erfolgreich an PHP gesendet:', result);
            portalInhalt.insertAdjacentHTML("beforeend", "<p>✅ Datensätze sind für die Übertragung vorbereitet.</p>");
            portalInhalt.insertAdjacentHTML("beforeend", result);
            portalInhalt.insertAdjacentHTML("beforeend", "<p>✅ Abrechnung erfolgreich an Vereinsflieger übertragen.</p>");
            portalInhalt.insertAdjacentHTML("beforeend", "<p>⚠️ Bitte die Abrechnung in Vereinsflieger prüfen und bestätigen.</p>");
            portalInhalt.insertAdjacentHTML("beforeend", "<p>⚠️ Damit die neuen Buchungen sichtbar sind, muss die Seitenansicht mit F5 oder mit dem folgenden Button aktualisiert werden.</p>");    portalInhalt.insertAdjacentHTML("beforeend", "<button class='kleinerBt' onclick='location.reload();'>aktualisieren</button>");
            document.getElementById("preloader").style.display = "none";
            

            // Zurücksetzen der Variablen, um sicher zu stellen, dass sie nicht mehr verwendet werden, bis die Seite neu geladen wird
            verkäufe = [];
            produkte = [];
            wareneingang = [];
        })
        .catch(error => {
            console.error('❌ - Fehler beim Senden:', error);
            portalInhalt.insertAdjacentHTML("beforeend", error);
            alert('Daten können nicht übertragen werden!');
            document.getElementById("preloader").style.display = "none";
        });         
    }

    /**
     * Erstellt den HTML-Rechnungstext für einen Kunden
     * @param {string} kundennummer - ID des Kunden
     * @param {Date} datum1 - Startdatum
     * @param {Date} datum2 - Enddatum
     * @returns {string} - HTML-Rechnungstext
     */
    function RechnungstextErstellen(kundennummer, datum1, datum2) {

        let kunde = käufer.find(k => String(k.uid) === String(kundennummer));

        let html = `
            <html>
            <style>
                body {
                    margin: 0;
                    font-family: 'Courier New', Courier;
                    font-size: 14px; color: #000;
                    max-width: 600px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    text-align: left;
                    padding: 0 10px 0px 0;
                }
                th {
                    background-color: #f2f2f2;
                }
                pre {
                    margin: 0;
                }
            </style>
            <body>
                    <div>
                        <pre><b>${config.Vereinsname}</b></pre>
                        <pre>${config.Straße}</pre>
                        <pre>${config.PLZ} ${config.Ort}</pre>
                        <pre>Telefon: ${config.Telefon}</pre>
                        <pre>Email: ${config.Email}</pre>
                        <pre>USt-IdNr: ${config.UStID}</pre>
                        <pre>Bankverbindung: ${config.Bankverbindung}</pre>
                        <pre>IBAN: ${config.IBAN}</pre>
                        <pre>Kontoinhaber: ${config.Kontoinhaber}</pre>
                        <br> <br> <br>
                        <pre><b>${kunde.firstname} ${kunde.lastname}</b></pre>
                        <pre>${kunde.email}</pre>
                        <pre>${kunde.memberid ? 'Mitgliedsnr: ' + kunde.memberid : 'ohne Mitgliedsnr'}</pre>
                        <br> <br> <br>
                        <pre><b>Abrechnung ClubCash</b></pre>
                        <pre>Umsätze von ${datum1.toLocaleDateString('de-DE')} bis ${datum2.toLocaleDateString('de-DE')}</pre>
                        <pre>Stand: ${heute.toLocaleDateString('de-DE', { year: 'numeric', month: '2-digit', day: '2-digit' })}</pre>
                        <br> <br> <br>    
                    </div>

                    <div">
                        <table>
                            <thead>
                                <tr>        
                                    <th><pre>Datum</pre></th>
                                    <th><pre>Zeit</pre></th>
                                    <th><pre>Terminal</pre></th>
                                    <th><pre>Buchungstext</pre></th>
                                    <th style="text-align: right;"><pre>Preis</pre></th>
                                </tr>
                            </thead>
                            <tbody>`;
        let KäufeFilter = verkäufe.filter(auswahl => auswahl.Kundennummer == kundennummer && auswahl.Datum >= datum1.toISOString().split('T')[0] && auswahl.Datum <= datum2.toISOString().split('T')[0]);
        let summe = 0;
        KäufeFilter.forEach(verkauf => {
            html += `
                <tr>
                    <td><pre>${verkauf.Datum}</pre></td>
                    <td><pre>${verkauf.Zeit}</pre></td>
                    <td><pre>${verkauf.Terminal}</pre></td>
                    <td><pre>${verkauf.Produkt}</pre></td>
                    <td style="text-align: right; vertical-align: bottom;"><pre>${parseFloat(verkauf.Preis).toFixed(2)} €</pre></td>
                </tr>`;
            summe += parseFloat(verkauf.Preis);
        });
        html += `
                    </tbody>
                        <tfoot style="background-color: #f2f2f2">
                                <tr>
                                    <td colspan="3" style="text-align: right;"><pre><b>Summe</b></pre></td>
                                    <td style="text-align: right;"><pre><b>${summe.toFixed(2)} €</b></pre></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <hr>
                    <div>
                        <pre style="text-align: center;">Die Abrechnung wurde automatisiert erstellt mit <br> ClubCash - Das bargeldlose Bezahlsystem für Flugsportvereine <br>&copy; 2026 Marcel Schommer</pre>
                    </div>
                </div>    
            </body>
            </html>`;
        return html;
    }
    


// ============================================================================
// SYSTEM & ADMINISTRATION
// ============================================================================

    /**
     * Fügt HTML-Inhalt an portalInhalt an (statt zu ersetzen)
     * @param {string} htmlContent - Hinzuzufügender HTML-Code
     */
    function appendHTMLToPortalInhalt(htmlContent) {
        portalInhalt.insertAdjacentHTML("beforeend", htmlContent);
    }

    /**
     * Prüft auf verfügbare Updates von GitHub
     * Vergleicht lokale Version mit neuester Release-Version
     */
    function Update() {
        portalmenu2.innerHTML = "<h2 style='display: inline;'>Update</h2>";
        html = "";
        html += "<p>Installierte Version: " + config.Version + "</p>";
        html += "<p>Zeitunkt des letzten Updates: " + config.letzteAktualisierung + "</p>";
        html += "<p>Altuelles Release: " + release.tag_name + "</p>";
        html += "<p>Zeitpunkt des letzten Releases: " + release.published_at + "</p>";

        if (config.Version === release.tag_name) {
            html += "<p>✅ Die Software ist auf dem neuesten Stand.</p>";
        } else {
            html += "<p>⚠️ Es ist ein Update verfügbar. Bitte die Software aktualisieren.</p>";
            html += "<p>⚠️ Bitte vor dem Update manuell ein Systembackup erstellen.<br>Die aktuellen Daten werden dabei mit gesichert. Sollte das Backup fehlschlagen, kann mit dem Backup das System vollständig wiederherstellen oder es auf einem anderen System installieren werden.</p>";
            html += "<button class='kleinerBt' onclick='Systembackup()'>Systembackup</button>"
            html += `<button class='kleinerBt' onclick='Update2()'>Update</button>`;
        }

        portalInhalt.innerHTML = html;

    }

    /**
     * Führt Update-Prozess durch
     * Lädt update.php und zeigt Fortschritt an
     */
    function Update2() {
        portalmenu2.innerHTML = "<h2 style='display: inline;'>Update</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, das Update wird installiert...</p>";
        document.getElementById("preloader").style.display = "block";

        // AJAX request to perform the update
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "update.php", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                portalInhalt.innerHTML = xhr.responseText;
                document.getElementById("preloader").style.display = "none";
            }
        };
        xhr.send();
    }

    /**
     * Erstellt Systembackup aller Daten
     * Sichert JSON-Dateien und CSV-Umsätze
     */
    function Systembackup() {
        if (confirm('Soll jetzt ein Systembackup erstellt werden? Es kann mehrere Minuten dauern. Das Backup kann als ZIP-Datei im Anschluss heruntergeladen werden. Zusätzlich ist sie im Order /backup/ gespeichert.')) {
        
            portalmenu2.innerHTML = "<h2 style='display: inline;'>Systembackup</h2>";
            portalInhalt.innerHTML = "<p>Bitte warten, das Systembackup wird erstellt...</p>";
            document.getElementById("preloader").style.display = "block";

            // AJAX request to create backup
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "create-system-backup.php", true);
            xhr.withCredentials = true;
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    portalInhalt.innerHTML = xhr.responseText;
                    document.getElementById("preloader").style.display = "none";
                }
            };
            xhr.send(); 
        }
    }
    
    /**
     * Prüft Sicherheitseinstellungen des Systems
     * Überprüft Dateiberechtigungen und Konfiguration
     */
    function Sicherheitscheck() {
        portalmenu2.innerHTML = "<h2 style='display: inline;'>Sicherheitscheck</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, bis der Ceck durchgeführt wurde...</p>";
        document.getElementById("preloader").style.display = "block";

        fetch("sicherheitscheck.php", {
            method: "GET",
            credentials: "include" // damit Session-Cookie mitgeschickt wird
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Fehler beim Laden: " + response.status);
            }
            return response.text();
        })
        .then(data => {
            portalInhalt.innerHTML = data;
        })
        .catch(error => {
            portalInhalt.innerHTML = `<p>❌ ${error.message}</p>`;
        })
        .finally(() => {
            preloader.style.display = "none";
        });
    }

    /**
     * Startet Absicherungsprozess
     * Setzt Dateiberechtigungen und .htaccess-Regeln
     */
    function absicherungStarten() {
        portalmenu2.innerHTML = "<h2 style='display: inline;'>Absicherung</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, bis die Absicherung durchgeführt wurde...</p>";
        document.getElementById("preloader").style.display = "block";
    
        fetch("absicherung.php", {
            method: "GET",
            credentials: "include" // damit Session-Cookie mitgeschickt wird
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Fehler beim Laden: " + response.status);
            }
            return response.text();
        })
        .then(data => {
            portalInhalt.innerHTML = data;
        })
        .catch(error => {
            portalInhalt.innerHTML = `<p>❌ ${error.message}</p>`;
        })
        .finally(() => {
            preloader.style.display = "none";
        });
    
    }

// ============================================================================
// EINSTELLUNGEN & KONFIGURATION
// ============================================================================

    /**
     * Zeigt und speichert Programmeinstellungen
     * Verwaltet alle Konfigurationsparameter inkl. SMTP-Einstellungen
     */
    function Programmeinstellungen() {
        let menu2 = "<h2 style='display: inline;'>Konfiguration</h2>";
        menu2 += `
            <button id="saveButtonConfig" class="kleinerBt">speichern</button> 
            <button onclick="location.reload();" class="kleinerBt">abbruch</button>
            <button class='kleinerBt' onclick='Systembackup()'>Systembackup</button>
        `;    

        let html = "";

        // Formularfelder für jede Konfigurationseinstellung
        // Eingabefelder aktivieren, wenn der Benutzer Admin ist, sind die Eigenschaften nicht Rollen nichtrichtig deklariert, 
        // kann das Feld vorsichtshalber nicht deaktiviert werden und der Benutzer kann sich nicht aus versehen ausschließen.
        let inputdisabled = "disabled"; // Eingabefelder standardmäßig deaktiviert
        if (angemeldetesMitglied.cc_admin === true) {
            inputdisabled = ""; 
        }

        console.log("angemeldetesMitglied: ", angemeldetesMitglied);
        console.log("angemeldetesMitglied.cc_admin: ", angemeldetesMitglied.cc_admin);

        if (config.schlüssel == undefined || config.schlüssel === "") {
            console.warn("Schlüssel ist nicht definiert oder leer. Setze den Standardwert.");
            config.schlüssel = "key2designation";
        }
        html += `

            <p>⚠️ Bevor Änderungen vorgenommen werden, wird eine Systemsicherung empfohlen!</p>
            <div class="EinstellungsFrm" style="display: grid; grid-template-columns: 250px 350px 300px; gap: 10px; align-items: top;">
                                
                <!-- Zugriffsdaten -->
                    <p class="formularunterüberschrift">Zugriffsdaten</p>

                        <!-- APPKEY -->
                        <label>APPKEY</label>
                        <input disabled type="text" id="appkey" value="${config.appkey}" class="inputfeld">
                        <p class="beschreibung">Jeder Verein kann sich in Vereinsflieger einen sogenannte APPKEY 
                            generieren zu lassen. Diese ist notwendig, um auf die Daten von Vereinflieger für die API zuzugreifen. 
                            Die notwendigen Informationen erhält man bei Vereinsfleiger.de. Der KEY wurde beim Einrichten des 
                            Systems eingegeben und sollte nicht geändert werden, da andernfalls der Zugriff auf Vereinsflieger.de 
                            nicht mehr möglich ist. Der Wert ist in der Datei config.JSON gespeichert und kann dort ggf. 
                            angepasst werden.</p>  

                        <!-- Passwort für das Kassenmodul -->
                        <label>Passwort Kassenmodul</label>
                        <input type="password" id="kassenpw1" value="" class="inputfeld">
                        <p class="beschreibung">Für den Zugriff auf das Kassenmodul muss ein Passwort festgelegt werden.
                        Bei Aufruf des Kassenmoduls wird als Benutzername: kasse und als Passwort, dass hier ausgewählte
                        verwendet. Das Passwort muss indestens 12 Zeichen lang sein, und mindestens einen Großbuchstaben, 
                        einen Kleinbuchstaben und eine Ziffer besitzen.<br>
                        Nach Einrichtung oder Änderung des Passwortes mus ein Systemcheck durchgführt werden, um die 
                        Passwörter zu aktivieren.</p>  

                        <!-- Passwort für das Kassenmodul -->
                        <label>Pw Wiederholung</label>
                        <input type="password" id="kassenpw2" value="" class="inputfeld">
                        <p class="beschreibung">Bitte des Passwort wiederholen.</p>  

                <!-- Vereinsinformationen -->
                    <p class="formularunterüberschrift">Vereinsinformationen</p>

                        <!-- Vereinsname -->
                        <label>Vereinsname</label>
                        <input type="text" id="vereinName" value="${config.Vereinsname}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- VereinsAbkürzel -->
                        <label>Vereinskürzel</label>
                        <input type="text" id="vereinAbkuerzel" value="${config.VereinsnameAbk}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- Vereinsadresse -->
                        <label>Straße</label>
                        <input type="text" id="vereinAdresse" value="${config.Straße}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- VereinsPLZ -->
                        <label>PLZ</label>
                        <input type="text" id="vereinPLZ" value="${config.PLZ}" class="inputfeld">
                        <p class="beschreibung"></p>
                        
                        <!-- Vereinsort -->
                        <label>Ort</label>
                        <input type="text" id="vereinOrt" value="${config.Ort}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- VereinsTelefon -->
                        <label>Telefon</label>
                        <input type="text" id="vereinTelefon" value="${config.Telefon}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- VereinsEmail -->
                        <label>Email</label>
                        <input type="email" id="vereinEmail" value="${config.Email}" class="inputfeld">
                        <p class="beschreibung">Die E-Mail-Adresse des Vereins, der in der Abrechnung und als Antwortadresse angezeigt wird.</p>

                        <!-- VereinsWebseite -->
                        <label>Webseite</label>
                        <input type="text" id="vereinWebseite" value="${config.Webseite}" class="inputfeld">
                        <p class="beschreibung"></p>
                        
                        <!-- Kassenwart -->
                        <label>Kassenwart</label>
                        <input type="text" id="kassenwart" value="${config.Kassenwart}" class="inputfeld">
                        <p class="beschreibung"></p>
                        
                        <!-- Bankverbindung -->
                        <label>Bankverbindung</label>
                        <input type="text" id="bankverbindung" value="${config.Bankverbindung}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- IBAN -->
                        <label>IBAN</label>
                        <input type="text" id="iban" value="${config.IBAN}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- Kontoinhaber -->
                        <label>Kontoinhaber</label>
                        <input type="text" id="kontoinhaber" value="${config.Kontoinhaber}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- Steuernummer -->
                        <label>Steuernummer</label>
                        <input type="text" id="steuernummer" value="${config.Steuernummer}" class="inputfeld">
                        <p class="beschreibung"></p>

                        <!-- USt-IdNr -->
                        <label>USt-IdNr</label>
                        <input type="text" id="ustIdNr" value="${config.UStID}" class="inputfeld">
                        <p class="beschreibung"></p>

                <!-- Rollen -->
                    <p class="formularunterüberschrift">Rollen</p>

                        <!-- Gast -->
                        <label>Gast</label>
                        <input type="text" id="gast" value="${config.cc_guest}" class="inputfeld">
                        <p class="beschreibung">Die Rollenbezeichnung für Zugriffsrechte der Gäste. 
                        Hierzu muss eine benutzerdefiniertes Feld bei der Mitgliederbereuung für jedes Mitglied 
                        in Vereinsflieger eingerichtet sein. Hier wird die passende Bezeichtung des Benutzerdefinierten 
                        Feldes angegeben. Der Wert des Feldes muss true oder false sein.
                        Nach Änderung des Wertes müssen die Mitgliederdaten aus Vereinsflieger.de neu geladen werden.</p>

                        <!-- Mitglied -->
                        <label>Mitglied</label>
                        <input type="text" id="mitglied" value="${config.cc_member}" class="inputfeld">
                        <p class="beschreibung">Die Rollenbezeichnung für Zugriffsrechte der Mitglieder.
                        Hierzu muss eine benutzerdefiniertes Feld bei der Mitgliederbereuung für jedes Mitglied
                        in Vereinsflieger eingerichtet sein. Hier wird die passende Bezeichtung des Benutzerdefinierten
                        Feldes angegeben. Der Wert des Feldes muss true oder false sein.
                        Nach Änderung des Wertes müssen die Mitgliederdaten aus Vereinsflieger.de neu geladen werden.</p>

                        <!-- Verkäufer -->
                        <label>Verkäufer</label>
                        <input type="text" id="verkäufer" value="${config.cc_seller}" class="inputfeld">
                        <p class="beschreibung">Die Rollenbezeichnung für Zugriffsrechte der Verkäufer.
                        Hierzu muss eine benutzerdefiniertes Feld bei der Mitgliederbereuung für jedes Mitglied
                        in Vereinsflieger eingerichtet sein. Hier wird die passende Bezeichtung des Benutzerdefinierten
                        Feldes angegeben. Der Wert des Feldes muss true oder false sein.
                        Nach Änderung des Wertes müssen die Mitgliederdaten aus Vereinsflieger.de neu geladen werden.</p>

                        <!-- Admin -->
                        <label>Admin</label>
                        <input type="text" id="admin" value="${config.cc_admin}" class="inputfeld">
                        <p class="beschreibung">Die Rollenbezeichnung für Zugriffsrechte der Admins.
                        Hierzu muss eine benutzerdefiniertes Feld bei der Mitgliederbereuung für jedes Mitglied
                        in Vereinsflieger eingerichtet sein. Hier wird die passende Bezeichtung des Benutzerdefinierten
                        Feldes angegeben. Der Wert des Feldes muss true oder false sein.
                        Nach Änderung des Wertes müssen die Mitgliederdaten aus Vereinsflieger.de neu geladen werden.</p>


                <!-- Emailseinstellung -->
                    <p class="formularunterüberschrift">Email Einstellungen</p>

                        <!-- SMTP Server -->
                        <label>SMTP Server</label>
                        <input type="text" id="smtpServer" value="${config.SMTPServer}" class="inputfeld">
                        <p class="beschreibung">Der SMTP-Server, der für den Versand von E-Mails verwendet wird.</p>

                        <!-- SMTP Absenderadresse -->
                        <label>SMTP Absenderadresse</label>
                        <input type="email" id="smtpAbsenderadresse" value="${config.SMTPAbsenderadresse}" class="inputfeld">
                        <p class="beschreibung">Die Absenderadresse, die in den E-Mails verwendet
                            wird. Diese Adresse sollte gültig sein und zum SMTP-Server passen.</p>

                        <!-- SMTP Antwortadresse -->
                        <label>SMTP Antwortadresse</label>
                        <input type="email" id="smtpAntwortadresse" value="${config.SMTPAntwortadresse}" class="inputfeld">
                        <p class="beschreibung">Die Antwortadresse, die in den E-Mails verwendet
                            wird. Antworten auf die E-Mails werden an diese Adresse gesendet.</p>

                        <!-- SMTP Port -->
                        <label>SMTP Port</label>
                        <input type="text" id="smtpPort" value="${config.SMTPPort}" class="inputfeld">
                        <p class="beschreibung">Der Port des SMTP-Servers.</p>

                        <!-- SMTP Verschlüsselung -->
                        <label>SMTP Verschlüsselung</label>
                        <select id="smtpVerschluesselung" class="inputfeld">
                            <option value="none" ${config.SMTPVerschluesselung === "none" ? "selected" : ""}>Keine</option>
                            <option value="ssl" ${config.SMTPVerschluesselung === "ssl" ? "selected" : ""}>SSL</option>
                            <option value="tls" ${config.SMTPVerschluesselung === "tls" ? "selected" : ""}>TLS</option>
                        </select>
                        <p class="beschreibung">Die Verschlüsselungsmethode für die Verbindung zum SMTP-Server.</p>
                        
                        <!-- SMTP Benutzer -->
                        <label>SMTP Benutzer</label>
                        <input type="text" id="smtpBenutzer" value="${config.SMTPBenutzer}" class="inputfeld">
                        <p class="beschreibung">Der Benutzername für die Authentifizierung am SMTP-Server.</p>

                        <!-- SMTP Passwort -->
                        <label>SMTP Passwort</label>
                        <input type="password" id="smtpPasswort" value="" class="inputfeld">
                        <p class="beschreibung">Das Passwort für die Authentifizierung am SMTP-Server.</p>  

                        <label></label>
                        <button class="kleinerBt" onclick="Testemail()">Testemail senden</button>
                        <p class="beschreibung">Mit dem Button kann eine Testemail an die Antwortadresse gesendet werden. 
                        Alle geänderten Konfigurationen müssen zuvor gespeichert werden.</p>
                        
                <!-- Einstellungen -->
                    <p class="formularunterüberschrift">Einstellungen</p>
                        <!-- Ansicht im Backend einschränkgen, kann nur editiert werden, wenn der Benutzer die Rolle Admin hat -->
                        <label>Ansicht einschränken</label>
                        <label class="switch">
                            <input ${inputdisabled} type="checkbox" id="zugriffseinschränkung" ${config.zugriffseinschränkung === "true" ? "checked" : ""}>
                        <span class="slider round"></span>
                        </label>
                        <p class="beschreibung">Bei Aktivierung wird die Ansicht im Backend für bestimmte Benutzer je nach Rolle eingeschränkt. 
                        Nach den Einrichtigen sollte das Feld aktiviert sein. Achtung! Nur wenn du die Rolle ADMIN hast, 
                        kannst du die Funktion aktivieren oder ändern</p>

                        <!-- Wartungsmodus -->
                        <label>Wartungsmodus</label>
                        <label class="switch">
                            <input type="checkbox" id="wartungsmodus" ${config.wartungsmodus === "true" ? "checked" : ""}>
                            <span class="slider round"></span>
                        </label>
                        <p class="beschreibung">Bei Aktivierung des Wartungsmodus, ist der Zugriff auf das Kassenmodul nicht möglich.
                            Beim Aufrufen des Kassenmoduls wird ein Informationsfenster mit dem Hinweis auf den Wartungsmodus geöffnet.
                            Auch offline ist die Bedienung dann nicht möglich.</p>

                        <!-- Tagesabrechnung -->
                        <label>Tagesabrechnung:</label>
                        <label class="switch">
                            <input type="checkbox" id="tagesabrechnung" ${config.tagesabrechnung === "true" ? "checked" : ""}>
                            <span class="slider round"></span>
                        </label>
                        <p class="beschreibung">Bei Aktivierung besteht die Möglichkeit, in Kassenmodul sich eine aktuelle Tagesabrechnung
                        anzeigen zu lassen.</p>

                        <!-- tageszusammenfassung -->
                        <label>Tageszusammenfassung</label>
                        <label class="switch">
                            <input type="checkbox" id="tageszusammenfassung" ${config.tageszusammenfassung === "true" ? "checked" : ""}>
                            <span class="slider round"></span>
                        </label>
                        <p class="beschreibung">Bei Aktivierung besteht die Möglichkeit, in Kassenmodul sich eine aktuelle Tageszusammenfassung
                        anzeigen zu lassen. Diese zeigt alle Verkäufe des Tages an, gruppiert nach Produkt.</p>
 
                        <!-- kundentagesübersicht -->
                        <label>Kundentagesübersicht</label>
                        <label class="switch">
                            <input type="checkbox" id="kundentagesübersicht" ${config.kundentagesübersicht === "true" ? "checked" : ""}>
                            <span class="slider round"></span>
                        </label>
                        <p class="beschreibung">Bei Aktivierung besteht die Möglichkeit, in Kassenmodul sich eine Kundentagesübersicht
                        anzeigen zu lassen. Diese zeigt alle Verkäufe des Tages an, gruppiert nach Kunde.</p>
                        
                        <!-- preisanpassungessen -->
                        <label>Preisanpassungessen</label>
                        <label class="switch">
                            <input type="checkbox" id="preisanpassungessen" ${config.preisanpassungessen === "true" ? "checked" : ""}>
                            <span class="slider round"></span>
                        </label>
                        <p class="beschreibung">Bei Aktivierung besteht die Möglichkeit, in Kassenmodul den Preis für das Essen anzupassen oder zu ändern.
                        </p>

                        <!-- Sanduhr -->
                        <label>Sanduhr</label>
                        <input type="text" id="sanduhrenZeit" value="${config.sanduhr}" class="inputfeld">
                        <p class="beschreibung">Die Verzögerungszeit in Sekunden, die im Kassenmodul 
                        nach dem Verkauf vor dem Wechsel der Ansicht pausiert.</p>

                        <!-- Bildschirmschoner -->
                        <label>Bildschirmschoner</label>
                        <input type="text" id="bildschirmschonerZeit" value="${config.bildschirmschoner}" class="inputfeld">
                        <p class="beschreibung">Die Verzögerungszeit in Minuten, die im Kassenmodul den Bildschirmschoner aktiviert.
                        </p>

                        <!-- Artikelnummer -->
                        <label>Artikelnummer VF</label>
                        <input type="text" id="artikelnummerVF" value="${config.ArtikelnummerVF}" class="inputfeld">
                        <p class="beschreibung">Die Artikelnummer, die in Vereinsflieger für die Verkäufe verwendet wird.
                        Diese wird für die Übertragung der Verkaufsdaten an Vereinsflieger benötigt.</p>

                        <!-- Schlüsselbezeichnung aus Datensatz Vereinsflieger -->
                        <label>Schlüsselbezeichnung</label>
                        <input type="text" id="schlüssel" value="${config.schlüssel}" class="inputfeld" >
                        <p class="beschreibung">Die Bezeichnung des Bezahlschlüssels, der in Vereinsflieger für die Verkäufe verwendet wird.
                        Das ist die Nummer, mit der die Mitglieder bezahlen können.</p>
                        

                        <!-- Offline erlaubt -->
                        <label>Offline erlaubt</label>
                        <label class="switch">
                            <input type="checkbox" id="offlineErlaubt" ${config.OfflineErlaubt === "true" ? "checked" : ""}>
                            <span class="slider round"></span>
                        </label>
                        <p class="beschreibung">Erlauben, dass die Kasse auch offline Buchungen vornimmt. Die Kasse muss die Funktion unterstützen, die auch getestet werden sollte.</p>

            </div>`;

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

        // Event Listeners für Passwortfelder
        const pw1Field = document.getElementById('kassenpw1');
        const pw2Field = document.getElementById('kassenpw2');
        const saveButton = document.getElementById('saveButtonConfig');

        // Event Listener für beide Passwortfelder
        pw1Field.addEventListener('input', validatePasswords);
        pw2Field.addEventListener('input', validatePasswords);

        // Event Listener für alle andere Felder, die nicht leer bleiben dürfen
        ['appkey', 'gast', 'mitglied', 'verkäufer', 'admin', 'artikelnummerVF', 'schlüssel'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            field.addEventListener('input', () => {
                // Validate that field is not empty
                field.style.backgroundColor = field.value.trim() === '' ? '#ffcccc' : '';
                // Disable save button if any required field is empty
                saveButton.disabled = ['appkey', 'gast', 'mitglied', 'verkäufer', 'admin', 'artikelnummerVF', 'schlüssel'].some(id => 
                    document.getElementById(id).value.trim() === ''
                );
            });
            // Initial validation
            field.dispatchEvent(new Event('input'));
        });

        // Initial validation
        validatePasswords();

        // Funktion zum Abrufen des Apache-kompatiblen Passwort-Hashes
        async function getHashFromPHP(password) {
            try {
                const response = await fetch('generate_htpasswd.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'password=' + encodeURIComponent(password)
                });

                const data = await response.json();

                if (data.error) {
                    console.error('Fehler:', data.error);
                    return null;
                }

                return data.htpasswd;
            } catch (error) {
                console.error('Fehler bei Hash-Generierung:', error);
                return null;
            }
        }

        // Event Listener für den Speichern-Button
        document.getElementById('saveButtonConfig').addEventListener('click', async () => {

            const pw1 = document.getElementById('kassenpw1').value;
            let hashedPw;

            if (pw1 === "") {
                hashedPw = config.kassenpw; // Altes Passwort beibehalten
            } else {
                try {
                    hashedPw = await getHashFromPHP(pw1);
                    if (!hashedPw) {
                        throw new Error("Failed to generate password hash.");
                    }
                } catch (error) {
                    alert("Error: " + error.message);
                    return;
                }
                if (!hashedPw) {
                    alert("Fehler beim Generieren des Passwort-Hashes!");
                    return;
                }
            }

            // SMTP Passwort behandeln

            const smtpPwInput = document.getElementById('smtpPasswort').value;
            let smtp_pw_neu;
            if (smtpPwInput === "") {
                smtp_pw_neu = config.SMTPPasswort; // Altes Passwort beibehalten
            } else {
                smtp_pw_neu = smtpPwInput; // Neues Passwort verwenden
            }

            const newConfig = {
                // Zugriffsdaten 
                appkey: document.getElementById('appkey').value,
                kassenpw: hashedPw,

                // Vereinsinformationen
                Vereinsname: document.getElementById('vereinName').value,
                VereinsnameAbk: document.getElementById('vereinAbkuerzel').value,
                Straße: document.getElementById('vereinAdresse').value,
                PLZ: document.getElementById('vereinPLZ').value,
                Ort: document.getElementById('vereinOrt').value,
                Telefon: document.getElementById('vereinTelefon').value,
                Email: document.getElementById('vereinEmail').value,
                Webseite: document.getElementById('vereinWebseite').value,
                Kassenwart: document.getElementById('kassenwart').value,
                Bankverbindung: document.getElementById('bankverbindung').value,
                IBAN: document.getElementById('iban').value,
                Kontoinhaber: document.getElementById('kontoinhaber').value,
                Steuernummer: document.getElementById('steuernummer').value,
                UStID: document.getElementById('ustIdNr').value,

                // Rollen
                cc_guest: document.getElementById('gast').value,
                cc_member: document.getElementById('mitglied').value,
                cc_seller: document.getElementById('verkäufer').value,
                cc_admin: document.getElementById('admin').value,

                // Email Einstellungen
                SMTPServer: document.getElementById('smtpServer').value,
                SMTPAbsenderadresse: document.getElementById('smtpAbsenderadresse').value,
                SMTPAntwortadresse: document.getElementById('smtpAntwortadresse').value,
                SMTPPort: document.getElementById('smtpPort').value,
                SMTPVerschluesselung: document.getElementById('smtpVerschluesselung').value,
                SMTPBenutzer: document.getElementById('smtpBenutzer').value,
                SMTPPasswort: smtp_pw_neu,
                
                // Einstellungen
                zugriffseinschränkung: document.getElementById('zugriffseinschränkung').checked.toString(),
                wartungsmodus: document.getElementById('wartungsmodus').checked.toString(),
                tagesabrechnung: document.getElementById('tagesabrechnung').checked.toString(),
                tageszusammenfassung: document.getElementById('tageszusammenfassung').checked.toString(),
                kundentagesübersicht: document.getElementById('kundentagesübersicht').checked.toString(),
                preisanpassungessen: document.getElementById('preisanpassungessen').checked.toString(),
                sanduhr: document.getElementById('sanduhrenZeit').value,
                bildschirmschoner: document.getElementById('bildschirmschonerZeit').value,
                ArtikelnummerVF: document.getElementById('artikelnummerVF').value,
                schlüssel: document.getElementById('schlüssel').value,
                OfflineErlaubt: document.getElementById('offlineErlaubt').checked.toString(),

                // Übernommene Daten
                Version: config.Version,
                letzteAktualisierung: config.letzteAktualisierung,
                demo: config.demo
                
            };

            // An Server senden
            fetch('json-schreiben.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    data: newConfig,
                    filename: 'daten/config.json'
                })
            })
            .then(response => response.text())
            .then(result => {
                alert('Konfiguration erfolgreich gespeichert');
                config = newConfig; // Update global config
                // Aktualisiere die die Seite
                location.reload();
            })
            .catch(error => {
                alert('Fehler beim Speichern der Konfiguration: ' + error);
            });
        });

        function validatePasswords() {

            const pw1 = pw1Field.value;
            const pw2 = pw2Field.value;

            if (!pw1 && !pw2) {
                return;
            }
            
            // Password validation rules
            const minLength = 12;
            const hasLowerCase = /[a-z]/.test(pw1);
            const hasUpperCase = /[A-Z]/.test(pw1);
            const hasNumber = /[0-9]/.test(pw1);
            
            // Check all validation rules
            const isValidFormat = pw1.length >= minLength && 
                                    hasLowerCase && 
                                    hasUpperCase && 
                                    hasNumber;
            
            // Check if passwords match and meet format requirements
            const isValid = pw1 && pw2 && pw1 === pw2 && isValidFormat;
            
            // Set visual feedback
            pw1Field.style.backgroundColor = isValid ? '' : '#ffcccc';
            pw2Field.style.backgroundColor = isValid ? '' : '#ffcccc';
            saveButton.disabled = !isValid;
                
        }

    }
	
    /**
     * Lädt alle Mitgliederdaten aus Vereinsflieger
     * Ruft API auf und zeigt Ergebnis an
     */
	function Mitgliederdaten() {
		portalmenu2.innerHTML = "<h2 style='display: inline;'>Vereinsflieger Datenimport</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, die Mitgliederdaten werden aus Vereinsflieger abgerufen...</p>";
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "pull_Mitgliedsdaten_Vereinsflieger_alle.php", true); 
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                portalInhalt.innerHTML = xhr.responseText;
            }
        };
        xhr.send();
	}
	
// ============================================================================
// DATEI-DOWNLOADS
// ============================================================================

    /**
     * Lädt Datei herunter (JSON oder CSV)
     * Erstellt temporären Download-Link und entfernt ihn nach Download
     * @param {string} filename - Pfad zur herunterzuladenden Datei
     */
    function downloadFile(filename) {

        // Erstellung des Download Links
        const downloadUrl = `download.php?file=${encodeURIComponent(filename)}`;

        // Erstelle ein unsichtbares <a> Element
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = filename.split('/').pop(); // Extrahiere den Dateinamen ohne Pfad
        
        // Füge das Element zum DOM hinzu
        document.body.appendChild(link);
        
        try {
            // Klicke den Link programmatisch
            link.click();
            
            // Entferne den Link wieder nach kurzer Verzögerung
            setTimeout(() => {
                document.body.removeChild(link);
            }, 100);
        } catch (error) {
            console.error('Fehler beim Download:', error);
            alert('Fehler beim Download der Datei');
        }
    }

    function installLöschen() {
        if (confirm('Soll die Datei install.php gelöscht werden?')) {
            portalmenu2.innerHTML = "<h2 style='display: inline;'>Installationsdatei löschen</h2>";
            portalInhalt.innerHTML = "<p>Bitte warten, die Datei wird gelöscht...</p>";
            document.getElementById("preloader").style.display = "block";

            // AJAX request to delete the file
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "delete_install.php", true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    portalInhalt.innerHTML = xhr.responseText;
                    document.getElementById("preloader").style.display = "none";
                }
            };
            xhr.send(); 
        }
    }
  
    function downloadBackup(filename) {

        fetch('download.php?file=' + encodeURIComponent(filename), {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) throw new Error('Download fehlgeschlagen');
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            setTimeout(() => {
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }, 100);
        })
        .catch(error => {
            console.error('Download Fehler:', error);
            alert('Fehler beim Download: ' + error.message);
        });
        
        return false;
    }

// ============================================================================
// E-MAIL FUNKTIONEN
// ============================================================================

    /**
     * Sendet Test-E-Mail zur Überprüfung der SMTP-Konfiguration
     * Ruft send_test_email.php auf und zeigt Ergebnis an
     */
    function Testemail() {
        portalInhalt.innerHTML = "<p>Bitte warten, die Testemail wird gesendet...</p>";
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "send_test_email.php", true); 
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                portalInhalt.innerHTML = xhr.responseText;
            }
        };
        xhr.send();
        //Fehlermeldung abfangen
        xhr.onerror = function () {
            portalInhalt.innerHTML = "<p>Fehler beim Senden der Testemail. Bitte überprüfen Sie die SMTP-Einstellungen und Ihre Internetverbindung.</p>";
        };
    }   

    /**
     * Sendet Einzelrechnung per E-Mail (Platzhalter-Funktion)
     * @param {string} kundennummer - ID des Kunden
     * @param {Date} datum1 - Startdatum
     * @param {Date} datum2 - Enddatum
     */
    function Emailrechnung(kundennummer, datum1, datum2) {

        console.log("Sende Einzelrechnung an KundeID: " + kundennummer + " für Zeitraum: " + datum1 + " bis " + datum2);

        let Statusmeldung;

        let kunde = käufer.find(k => String(k.uid) === String(kundennummer));

        if (!kunde.email || kunde.email.trim() === "") {
            let Statusmeldung = "<p>❌ <b>" + kunde.firstname + ", " + kunde.lastname + "</b> hat keine E-Mail-Adresse. Rechnung wurde nicht gesendet.</p>";
            //portalInhalt.innerHTML = Statusmeldung;
            return Statusmeldung;
        }

        // Datum-Strings in Date-Objekte umwandeln
        if (typeof datum1 === 'string') datum1 = new Date(datum1);
        if (typeof datum2 === 'string') datum2 = new Date(datum2);

        // HTML-Inhalt für die Abrechnung erstellen
        let html = RechnungstextErstellen(kundennummer, datum1, datum2);
        
       // E-Mail Senden via AJAX
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "Emailrechnung.php", true); 
        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    //alert("Rechnung erfolgreich per E-Mail gesendet.");
                    Statusmeldung = "<p>✅ Rechnung an <b>" + kunde.firstname + ", " + kunde.lastname + "</b> (" + kunde.email + ") gesendet</p>";
                    //portalInhalt.innerHTML = Statusmeldung;
                } else {
                    //alert("Fehler beim Senden der Rechnung per E-Mail: " + xhr.responseText);
                    Statusmeldung = "<p>❌ Es konnte keine Rechnung an <b>" + kunde.firstname + ", " + kunde.lastname + "</b> gesendet werden. Fehler: " + xhr.responseText + "</p>";
                    //portalInhalt.innerHTML = Statusmeldung;
                }
            }
        };

        //Ich möchte die html als Anlage senden

        xhr.send(JSON.stringify({
            kundennummer: kundennummer,
            name: kunde.lastname,
            vorname: kunde.firstname,
            email: kunde.email,
            datum1: datum1.toISOString().split('T')[0],
            datum2: datum2.toISOString().split('T')[0],
            html: html,
            anhang: true
        }));

        return Statusmeldung;
    }

    /**
     * Sendet Abrechnungs-E-Mails an alle Kunden mit Umsatz im Zeitraum
     * @param {Date|string} datum1 - Startdatum
     * @param {Date|string} datum2 - Enddatum
     */
    function Emailabrechnung_alle(datum1, datum2) {

        // Datum-Strings in Date-Objekte umwandeln
        if (typeof datum1 === 'string') datum1 = new Date(datum1);
        if (typeof datum2 === 'string') datum2 = new Date(datum2);
       
        portalmenu2.innerHTML = "<h2>E-Mail Versand läuft...</h2>";
        portalInhalt.innerHTML = "<p>Bitte warten, E-Mails werden versendet...</p>";

        let gesamtStatus = "<h3>E-Mail Versand Status</h3>";
        let erfolgreich = 0;
        let fehler = 0;

        // Nur Kunden mit Umsatz im Zeitraum
        const kundenMitUmsatz = kunden.filter(kunde => {
            return verkäufe.some(verkauf => 
                verkauf.Kundennummer === kunde.uid &&
                verkauf.Datum >= datum1.toISOString().split('T')[0] && 
                verkauf.Datum <= datum2.toISOString().split('T')[0]
            );
        });

        if (kundenMitUmsatz.length === 0) {
            portalInhalt.innerHTML = "<p>⚠️ Keine Mitglieder mit Umsatz im gewählten Zeitraum gefunden.</p>";
            return;
        }

        gesamtStatus += `<p>Versende ${kundenMitUmsatz.length} Abrechnungen...</p>`;
        portalInhalt.innerHTML = gesamtStatus;

        // E-Mails sequenziell versenden
        let index = 0;

        function sendeNächsteEmail() {
            if (index >= kundenMitUmsatz.length) {
                // Fertig
                gesamtStatus += `<hr><p><b>Fertig!</b> ✅ ${erfolgreich} erfolgreich | ❌ ${fehler} Fehler</p>`;
                portalInhalt.innerHTML = gesamtStatus;
                return;
            }

            const kunde = kundenMitUmsatz[index];
            if (!kunde.email || kunde.email.trim() === "") {
                fehler++;
                gesamtStatus += `<p>❌ ${index + 1}/${kundenMitUmsatz.length} - <b>${kunde.firstname} ${kunde.lastname}</b> hat keine E-Mail-Adresse. Abrechnung wurde nicht gesendet.</p>`;
                portalInhalt.innerHTML = gesamtStatus;
                index++;
                // Nächste E-Mail nach kurzer Verzögerung
                setTimeout(sendeNächsteEmail, 500);
                return; 
            }
            index++;

            // E-Mail senden
            const html = RechnungstextErstellen(kunde.uid, datum1, datum2);
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "Emailrechnung.php", true); 
            xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        erfolgreich++;
                        gesamtStatus += `<p>✅ ${index}/${kundenMitUmsatz.length} - <b>${kunde.firstname} ${kunde.lastname}</b> (${kunde.email})</p>`;
                    } else {
                        fehler++;
                        gesamtStatus += `<p>❌ ${index}/${kundenMitUmsatz.length} - <b>${kunde.firstname} ${kunde.lastname}</b> - Fehler: ${xhr.responseText}</p>`;
                    }
                    portalInhalt.innerHTML = gesamtStatus;
                    
                    // Nächste E-Mail nach kurzer Verzögerung
                    setTimeout(sendeNächsteEmail, 500);
                }
            };

            xhr.send(JSON.stringify({
                kundennummer: kunde.uid,
                name: kunde.lastname,
                vorname: kunde.firstname,
                email: kunde.email,
                datum1: datum1.toISOString().split('T')[0],
                datum2: datum2.toISOString().split('T')[0],
                html: html,
                anhang: true
            }));
        }

        sendeNächsteEmail();
    }

function TankstellenQRCode(ID) {
    if (!ID) {
        // aktuelle Schlüsselnummer des angemeldeten Benutzers verwenden
        if (angemeldetesMitglied) {
            const keyField = (config && typeof config.schlüssel === 'string') ? config.schlüssel : '';
            ID = (keyField && angemeldetesMitglied[keyField])
                ? angemeldetesMitglied[keyField]
                : (angemeldetesMitglied.schlüssel || angemeldetesMitglied.Schlüssel || '');
        }

        if (!ID) {
            alert("Fehler: Keine Schlüsselnummer für den aktuellen Benutzer gefunden.");
            return;
        } else {
            ID = String(ID).trim();
        }

        if (!ID) {
            alert("Fehler: Keine gültige Schlüsselnummer für den aktuellen Benutzer gefunden.");
            return;
        }

        if (angemeldetesMitglied && !angemeldetesMitglied[config.schlüssel] && angemeldetesMitglied.schlüssel) {
            console.warn(`Hinweis: config.schlüssel='${config.schlüssel}' nicht im Benutzerobjekt gefunden, Fallback 'schlüssel' verwendet.`);
        }
    }
    portalmenu2.innerHTML = "<h2 style='display: inline;'>ClubCash Tankstelle</h2>";
    portalInhalt.innerHTML = "<p>Bitte warten, der QR-Code wird generiert...</p>";

    // AJAX request to get QR code
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "tanken_qr_code.php?ID=" + encodeURIComponent(ID), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            portalInhalt.innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}


</script>
</body>
</html>
