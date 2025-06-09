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

// Mitgliederdaten laden
clearstatcache(true, "daten/kunden.json"); // Clear file cache for this specific file
$jsonKundenDatei = file_get_contents("daten/kunden.json");
$jsonKundenDaten = json_decode($jsonKundenDatei, true); // true gibt ein assoziatives Array zurück

// Produkte laden
clearstatcache(true, "daten/produkte.json"); // Clear file cache for this specific file
$jsonProdukteDatei = file_get_contents("daten/produkte.json");
$jsonProdukteDaten = json_decode($jsonProdukteDatei, true); // true gibt ein assoziatives Array zurück

// wareneingang laden
clearstatcache(true, "daten/wareneingang.json"); // Clear file cache for this specific file
$jsonWareneingangDatei = file_get_contents("daten/wareneingang.json");
$jsonWareneingangDaten = json_decode($jsonWareneingangDatei, true); // true gibt ein assoziatives Array zurück

// Configurationsdatei einbinden
    $jsonConfigDatei = file_get_contents("daten/config.json");
    $jsonConfigDaten = json_decode($jsonConfigDatei, true); // true gibt ein assoziatives Array zurück
    
// Mitgliederdaten laden
    $jsonKundenDatei = file_get_contents("daten/kunden.json");
    $jsonKundenDaten = json_decode($jsonKundenDatei, true); // true gibt ein assoziatives Array zurück

// Produkte laden
    $jsonProdukteDatei = file_get_contents("daten/produkte.json");
    $jsonProdukteDaten = json_decode($jsonProdukteDatei, true); // true gibt ein assoziatives Array zurück

// Wareneingang laden
    $jsonWareneingangDatei = file_get_contents("daten/wareneingang.json");
    $jsonWareneingangDaten = json_decode($jsonWareneingangDatei, true); // true gibt ein assoziatives Array zurück

// csv umsatz laden
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
	
	<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
	
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
            <h1>ClubCash Portal</h1>
                        <p>&nbsp;<b><span id="Version">x.x.x</span></b></p>
	</div>
    
<nav class="navbar">
  <ul>
    <li>
      <a href="#" id="MenuMeinKonto" style="display: none;">Mein Konto</a>
      <ul>
        <li><a href="" onclick="location.reload()">Programminfo</a></li>
        <li><a href="#" onclick="Kontoübersicht()">Übersicht</a></li>
        <li><a href="logout.php" >Abmelden</a></li>
      </ul>
    </li>   

    <li>
      <a href="#" id="MenuAuswertung" style="display: none;">Auswertung</a>
      <ul>
        <li><a href="#" onclick="Tagesumsätze()">Tagesumsätze</a></li>
        <li><a href="#" onclick="Tageszusammenfassung()">Tageszusammenfassung</a></li>
        <li><a href="#" onclick="Kundentagesübersicht()">Kundentagesumsätze</a></li>     
        <li><a href="#" onclick="Preisliste_drucken()">Preisliste</a></li>
        <li><a href="#" onclick="Preisliste_strichcode()">Strichcodeliste</a></li>
        <li><a href="#" onclick="Preisliste_Eiskarte()">Eiskarte</a></li></ul>
    </li>

    <li>
      <a href="#" id="MenuAdministrator" style="display: none;">Administration</a>
      <ul>
        <li><a href="#" onclick="Mitgliedsdaten_ziehen()">Kundenliste aktualisieren</a></li>
        <li><a href="#" onclick="Mitgliederdaten_anzeigen()">Kundenliste</a></li>
        <li><a href="#" onclick="Umsätze()">Umsätze</a></li>
        <li><a href="#" onclick="Produkte_editieren()">Produkte</a></li>
        <li><a href="#" onclick="Wareneingang()">Wareneingang</a></li>
        <li><a href="#" onclick="Abrechnung()">Abrechnung</a></li>
      </ul>
    </li>
 
    <li>
      <a href="#" id="MenuEinstellungen" style="display: none;">Einstellungen</a>
      <ul>
        <li><a href="#" onclick="Farben()">Farben</a></li>
        <li><a href="#" onclick="Programmeinstellungen()">Porgrammeinstellungen</a></li>
        <li><a href="#" onclick="Update()">Update</a></li>
        <li><a href="#" onclick="Systembackup()">Systembackup</a></li>
        <li><a href="#" onclick="Sicherheitscheck()">Sicherheitscheck</a></li>
        <li><a href="#" class="disabled">alle Daten löschen</a></li>
      </ul>
    </li>
    <li>
      <a href="#" id="MenuDownload" style="display: none;">Download</a>
      <ul>
        <li><a href="daten/produkte.json" >Produktliste JSON</a></li>
        <li><a href="daten/kunden.json" >Kundenliste JSON</a></li>
        <li><a href="daten/umsatz.csv" >umsatz CSV</a></li>
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

    let portalmenu2 = document.getElementById('portalmenu2');

    // Datum mitteleuropäisch formatiert
        let heute = new Date();
        heute = new Date(heute.getTime() - heute.getTimezoneOffset() * 60000);

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
        const kunden = <?php echo json_encode($jsonKundenDaten); ?>;
        let produkte = <?php echo json_encode($jsonProdukteDaten); ?>;
        let verkäufe = <?php echo json_encode($verkäufe); ?>;
        let wareneingang = <?php echo json_encode($jsonWareneingangDaten); ?>;
        let customer_login = <?php echo json_encode($_SESSION['customer_login']); ?>;
        let config = <?php echo json_encode($jsonConfigDaten); ?>;
        const release = <?php echo json_encode($release); ?>;

    // Version anzeigen
        document.getElementById('Version').textContent = config.Version;

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


    //aktuelle Kontostände der Kunden berechnen
        let kundenkontostand = Kundenkontostand(verkäufe);
        const portalInhalt = document.getElementById('portal-inhalt');
        const portalMenu = document.getElementById('portal-menu');

    // Finde das angemeldete Mitglied anhand der Email-Adresse (case-insensitive)
        const angemeldetesMitglied = kunden.find(kunde => 
            kunde.email.toLowerCase() === '<?php echo strtolower($_SESSION['username']); ?>');
        document.getElementById('userName').textContent = angemeldetesMitglied.firstname + " " + angemeldetesMitglied.lastname;
    
    //Menu gemäß Rollen ein- und ausblenden
        if (customer_login === true && angemeldetesMitglied.cc_seller === true) {
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (customer_login === true ) {
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'none';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (angemeldetesMitglied.cc_admin === true) {
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'block';
            document.getElementById('MenuEinstellungen').style.display = 'block';
            document.getElementById('MenuDownload').style.display = 'block';
        } else if (angemeldetesMitglied.cc_seller === true) {
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'block';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (angemeldetesMitglied.cc_member === true) {
            document.getElementById('MenuMeinKonto').style.display = 'block';
            document.getElementById('MenuAuswertung').style.display = 'none';
            document.getElementById('MenuAdministrator').style.display = 'none';
            document.getElementById('MenuEinstellungen').style.display = 'none';
            document.getElementById('MenuDownload').style.display = 'none';
        } else if (angemeldetesMitglied.cc_guest === true) {
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

    function Preisliste_Eiskarte() {
        const heute = new Date();

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
	            <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
	
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
                    <button onclick="window.print();">drucken</button>
                </div>
            </body>
            </html>
        `;

        // HTML in neues Fenster schreiben
        printWindow.document.write(html);
        printWindow.document.close();
    }

    function Preisliste_drucken() {
        // Öffne neues Fenster mit der Preisliste
        let printWindow = window.open('', '_blank', 'width=800,height=600');
        
        // HTML für die Preisliste erstellen
        let html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Preisliste</title>
                <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
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
            .filter(p => p.Bezeichnung !== 'Direktbuchung' && p.Bezeichnung !== 'Essen')
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

    function Preisliste_strichcode() {

        // Öffne neues Fenster mit der Preisliste
        let printWindow = window.open('', '_blank', 'width=800,height=600');
        
        // HTML für die Preisliste erstellen
        let html = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Preisliste</title>
                <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
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
            .filter(p => p.Bezeichnung !== 'Direktbuchung' && p.Bezeichnung !== 'Essen')
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
            let keys = Object.keys(data[0] || {});

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
                newRows.clear();
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

    function Programmeinstellungen() {
        console.log("Programmeinstellungen andeshen und...");

        portalmenu2.innerHTML = "<h2 style='display: inline;'>Konfiguration</h2>";
        
        let menu2 = "<h2 style='display: inline;'>Konfiguration</h2>";
        menu2 += `
            <button id="saveButton" class="kleinerBt">speichern</button>
            <button onclick="location.reload();" class="kleinerBt">abbruch</button>
        `;

        portalInhalt.innerHTML = `
            <table id="configTable" class="portal-table">
                <thead>
                    <tr>
                        <th class="links">Schlüssel</th>
                        <th class="links">Wert</th>
                    </tr>
                </thead>
                <tbody id="configTableBody">
                </tbody>
            </table>
        `;

        portalmenu2.innerHTML = menu2;

        const configTableBody = document.getElementById('configTableBody');
        const addButton = document.getElementById('addButton');
        const saveButton = document.getElementById('saveButton');

        // Hilfsfunktion zum Rendern der Tabelle
        function renderConfigTable() {
            configTableBody.innerHTML = '';
            Object.entries(config).forEach(([key, value]) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="links">${key}</td>
                    <td class="links" contenteditable="true">${value}</td>
                `;
                configTableBody.appendChild(tr);
            });
        }

        // Speichern der Konfiguration
        saveButton.onclick = () => {
            // Neues Config-Objekt erstellen
            const newConfig = {};
            document.querySelectorAll('#configTableBody tr').forEach(row => {
                const key = row.cells[0].textContent.trim();
                const value = row.cells[1].textContent.trim();
                if (key && value) {
                    newConfig[key] = value;
                }
            });

            // Config-Objekt aktualisieren
            config = newConfig;

            // An Server senden
            fetch('json-schreiben.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    data: config,
                    filename: 'daten/config.json'
                })
            })
            .then(response => response.text())
            .then(result => {
                alert('Konfiguration erfolgreich gespeichert');
            })
            .catch(error => {
                alert('Fehler beim Speichern der Konfiguration: ' + error);
            });
        };

        // Initial render
        renderConfigTable();

    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function Kontoübersicht() {
            Mitgliederdaten_anzeigen(); // Nur ausgeführt, um den aktuellen Kontostand zu aktualisieren
            Kundenübersicht(angemeldetesMitglied.uid);    
    }

    function Kundentagesübersicht() {

        portalmenu2.innerHTML = "<h2 style='display: inline;'>Kundentagesübersicht</h2>";
        let html = "<table class='portal-table'>";
        kunden.forEach(kunde => {
            let summe= 0;
            let htmlkunde = ""
            //htmlkunde += "<table class='portal-table'>";
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
            });
            htmlkunde += `
                <tr class="summenzeile">
                    <td colspan="4" class="rechts"><b>Summe</b></td>
                    <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
                </tr>
                <tr style="height: 10px;">
                    <td colspan="5"></td>
                </tr>
                
            `;
            if (summe > 0) {
                html += htmlkunde; // Nur anzeigen, wenn es Verkäufe gibt
            } 
           
        });
        html += '</table>';
        portalInhalt.innerHTML = html;

    }    

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
            let keys = Object.keys(data[0] || {});
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
                        if (['Bestand', 'Preis', 'Sortierung', 'MwSt', 'EAN', 'Min'].includes(key)) {
                            td.classList.add('rechts');
                        } else {
                            td.classList.add('links'); 
                        }

                        if (key === 'Bestand') {
                            td.contentEditable = !deletedRows.has(index);
                            td.innerText = item[key] || '';
                            // Prüfe ob Bestand unter Mindestbestand
                            if (item['Min'] && parseInt(item[key] || 0) < parseInt(item['Min'])) {
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
                            td.innerText = item[key];
                            td.onblur = () => {
                                if (isNaN(parseFloat(td.innerText)) && td.innerText !== '') {
                                    alert(`Bitte geben Sie eine gültige Zahl für ${key} ein.`);
                                    td.innerText = item[key];
                                    return;
                                }
                                if (key === 'EAN') {
                                    const newEAN = td.innerText;
                                    const isDuplicate = data.some((dataItem, dataIndex) => 
                                        dataIndex !== index && dataItem.EAN === newEAN
                                    );
                                    if (isDuplicate || newEAN === '1' || newEAN === '9990000000000') {
                                        alert('Diese EAN existiert bereits! Auch 1 und 9990000000000 sind nicht erlaubt. Bitte wählen Sie eine andere EAN.');
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

    function Mitgliederdaten_anzeigen() {
                
        let menu2 = "<h2 style='display: inline;'>Kundenliste</h2> Rolle: K = Kassenwart / V = Verkäufer / M = Mitglied / G = Gast";

        let html = '<table class="portal-table">';
        html += `
        <tr>
            <th>ID</th>
            <th class="links">Mitgliedsnr</th>
            <th class="links">Vorname</th>
            <th class="links">Nachname</th>
            <th class="links">Email</th>
            <th>Schlüssel</th>
            <th>K</th>
            <th>V</th>
            <th>M</th>
            <th>G</th>
            <th>Kontostand</h1>
            <th><i>i</i></th>
        </tr>`;

        kunden.forEach(kunde => {
            html += `<tr>
                <td>${kunde.uid}</td>
                <td class="links">${kunde.memberid}</td>
                <td class="links">${kunde.firstname}</td>
                <td class="links">${kunde.lastname}</td>
                <td class="links">${kunde.email}</td>
                <td>${kunde.key2designation}</td>`
                
                html += kunde.cc_admin ? "<td>✔️</td>" : "<td></td>";
                html += kunde.cc_seller ? "<td>✔️</td>" : "<td></td>";
                html += kunde.cc_member ? "<td>✔️</td>" : "<td></td>";
                html += kunde.cc_guest ? "<td>✔️</td>" : "<td></td>";

                kundenkontostandeinzeln = kundenkontostand.find(k => k.Kundennummer === kunde.uid);
                if (kundenkontostandeinzeln) {
                    kunde.Kontostand = kundenkontostandeinzeln.Summe;
                } else {
                    kunde.Kontostand = 0; // Standardwert, falls kein Kontostand gefunden wird
                }

                html += `<td class="rechts">${kunde.Kontostand} €</td>`;

                html += `<td><a style='text-decoration: none;' href='#' onclick='Kundenübersicht(${kunde.uid})'>ℹ️</a></td>`;                
                html += "</tr>";
        });

        html += '</table>';

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html;

    }	

    function Kundenübersicht(kundennummer,datum1,datum2) {

        let menu2 = "<h2 style='display: inline;'>Übersicht</h2>";
        
        let html = '';
        
        const kunde = kunden.find(kunde => kunde.uid == kundennummer);
        
        if(!datum1 || !datum2) {
            let datumjahr = heute.getFullYear();
            datum1 = new Date(datumjahr, 0, 1); // 1. Januar des aktuellen Jahres
            datum1.setHours(datum1.getHours() + 2); // 2 Stunden addieren
            datum2 = heute; // Aktuelles Datum
        } 
 

        KäufeFilter = verkäufe.filter(auswahl => auswahl.Kundennummer == kundennummer && auswahl.Datum >= datum1.toISOString().split('T')[0] && auswahl.Datum <= datum2.toISOString().split('T')[0]);

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
            <table style="border-spacing: 10px;">
                <tr>
                    <td>Name</td>
                    <td>${kunde.firstname} ${kunde.lastname}</td>
                </tr>                
                <tr>
                    <td>ID</td>
                    <td>${kunde.uid}</td>
                </tr>
                <tr>
                    <td>Mitgliedsnr</td>
                    <td>${kunde.memberid}</td>
                <tr>
                    <td>Email</td>
                    <td>${kunde.email}</td>
                </tr> 
                <tr>
                    <td>Schlüssel</td>
                    <td>${kunde.key2designation}</td>
                </tr>
                <tr>
                    <td>Rollen</td>
                    <td>${kunde.cc_admin ? "<mark>Kassenwart</mark>" : ""} ${kunde.cc_seller ? "<mark>Verkäufer</mark>" : ""} ${kunde.cc_member ? "<mark>Mitglied</mark>" : ""} ${kunde.cc_guest ? "<mark>Gast</mark>" : ""}</td>
                </tr>
                <tr>
                    <td>Kontostand</td>
                    <td>-${kunde.Kontostand} €</td>
                </tr>
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
                </tr>
            
        <tbody>`;

        KäufeFilter.forEach(verkauf => {    
            html += `<tr>
                <td>${verkauf.Terminal}</td>
                <td>${verkauf.Datum}</td>
                <td>${verkauf.Zeit}</td>
                <td class="links">${verkauf.Produkt}</td>
                <td class="links">${verkauf.Kategorie}</td>
                <td class="rechts">${verkauf.Preis} €</td>
            </tr>`
            summe += parseFloat(verkauf.Preis);
        });
        html += `
            <tr style="border-top: 1px solid black;">
                <td colspan="5" class="rechts"><b>Summe</b></td>
                <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
            </tr>
            <tr>
                <td colspan="5" class="rechts"><b>Übertrag</b></td>
                <td class="rechts"><b>-${(kunde.Kontostand - summe).toFixed(2)} €</b></td>
            </tr>
            <tr style="border-top: 1px solid black;">
                <td colspan="5" class="rechts"><b>Kontostand</b></td>
                <td class="rechts"><b>-${kunde.Kontostand} €</b></td>
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
            <tr>
                <td colspan="4" class="rechts"><b>Übertrag</b></td>
                <td class="rechts"><b>-${(kunde.Kontostand - summe).toFixed(2)} €</b></td>
            </tr>
            <tr style="border-top: 1px solid black;">
                <td colspan="4" class="rechts"><b>Kontostand</b></td>
                <td class="rechts"><b>-${kunde.Kontostand} €</b></td>
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
            <tr>
                <td colspan="2" class="rechts"><b>Übertrag</b></td>
                <td class="rechts"><b>-${(kunde.Kontostand - summe).toFixed(2)} €</b></td>
            </tr>
            <tr style="border-top: 1px solid black;">
                <td colspan="2" class="rechts"><b>Kontostand</b></td>
                <td class="rechts"><b>-${kunde.Kontostand} €</b></td>
            </tr>
        </tbody></table>`;
        

        portalmenu2.innerHTML = menu2;
        portalInhalt.innerHTML = html 

        const btn = document.getElementById("bt_aktualisierung");
        btn.addEventListener("click", () => {
            const datumA = document.getElementById("datum_anfang").value;
            const datumE = document.getElementById("datum_ende").value;
            Kundenübersicht(kundennummer,new Date(datumA), new Date(datumE));
        });
    }

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

    function backupliste() {
        portalmenu2.innerHTML = "<h2 style='display: inline;'>gespeicherte Backups</h2>";
        fetch('get-backup-files.php')
        .then(response => response.text())
        .then(data => {
            portalInhalt.innerHTML = data;
        })
        .catch(error => console.error('Fehler beim Laden der Dateien:', error));
    }

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

                Kunde = kunden.find(kunde => kunde.uid === verkauf.Kundennummer);
                
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

    function Umsätze(datum1, datum2) {

        // Wenn kein Datum angegeben ist, setze es auf den 1. Januar des aktuellen Jahres
        // und das aktuelle Datum
        if(!datum1 || !datum2) {
            let datumjahr = heute.getFullYear();
            datum1 = jahresbeginn
            datum2 = heute; // Aktuelles Datum
        } 
        
        let summe = 0;
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
                    <th class="rechts">Preis</th>
                </tr>

            <tbody>`;

            verkäufe.forEach(verkauf => {
            
                if (verkauf.Datum >= datum1.toISOString().split('T')[0] && verkauf.Datum <= datum2.toISOString().split('T')[0]) {

                    Kunde = kunden.find(kunde => kunde.uid === verkauf.Kundennummer);
                    
                    html += `
                        <tr>
                            <td>${verkauf.Terminal}</td>
                            <td>${verkauf.Datum}</td>
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
                <tr class="summenzeile">
                    <td colspan="5" class="rechts"><b>Summe</b></td>
                    <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
                </tr>
            </tbody></table>`;

        //Tabelle2 - Übersicht nach Produkten
            html += `<hr><h2 style="display: inline;"><a id="TabellenLink2" style='text-decoration: none;' href='#' onclick='toggleTabelle("Tabelle2", "TabellenLink2")'>➡️</a> Übersicht nach Produkten</h2>
                <table id="Tabelle2" class="portal-table" style="display: none; margin-top: 20px;">
                    <tr>
                        <th>Anzahl</th>
                        <th class="links">EAN</th>
                        <th class="links">Produkt</th>
                        <th class="links">Kategorie</th>
                        <th class="rechts">Einzelpreis</th> 
                        <th class="rechts">Gesamtpreis</th>
                    </tr>
                <tbody>
            `;

            summe = 0;

            produkte.forEach(produkt => {
                produktsumme = 0;
                produktanzahl = 0;
                let html2 = ""; // HTML für die Produktübersicht
                verkäufe.forEach(verkauf => {
                    if (verkauf.EAN === produkt.EAN && verkauf.Datum >= datum1.toISOString().split('T')[0] && verkauf.Datum <= datum2.toISOString().split('T')[0]) {
                        if (verkauf.Preis && !isNaN(parseFloat(verkauf.Preis))) {
                            produktsumme += parseFloat(verkauf.Preis);
                        }
                        produktanzahl++;
                    }
                });

                html2 += `
                    <tr>
                        <td>${produktanzahl}</td>
                        <td class="links">${produkt.EAN}</td>
                        <td class="links">${produkt.Bezeichnung}</td>
                        <td class="links">${produkt.Kategorie}</td>
                        <td class="rechts">${produkt.Preis} €</td>
                        <td class="rechts">${produktsumme.toFixed(2)} €</td>
                    </tr>`;

                if (produktanzahl > 0) { // Nur anzeigen, wenn es Verkäufe gibt
                    html += html2; // Nur anzeigen, wenn es Verkäufe gibt
                }
                    summe += produktsumme;
            });
            html += `
                <tr class="summenzeile">
                    <td colspan="5" class="rechts"><b>Summe</b></td>
                    <td class="rechts"><b>${summe.toFixed(2)} €</b></td>
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
                    Schlüssel: kunde.key2designation,
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
        let html = `

            <div class="datumauswahl">
                <input class="DatumInput" type="date" id="datum_anfang" value="${datum1.toISOString().split('T')[0]}">
                <h2 style="display: inline;"> bis </h2>
                <input class="DatumInput" type="date" id="datum_ende" value="${datum2.toISOString().split('T')[0]}">
                <button id="bt_aktualisierung" class="kleinerBt">aktualisieren</button>
                <button class="kleinerBt" onclick="Abrechnung(monatsbeginn, heute)">Monat</button>
                <button class="kleinerBt" onclick="Abrechnung(wochenbeginn, heute)">Woche</button>
                <button class="kleinerBt" onclick="Abrechnung(heute, heute)">Tag</button>
                <br>
                <button id="bt-abrechnungExport" class="kleinerBt">Export an VF</button>
                <button id="bt-Kontoausgleich" class="kleinerBt">zurücksetzen</button>
            </div>

            <table class="portal-table">
                <tr>
                    <th>bookingdate</th>
                    <th>articleid</th>
                    <th>memberid</th>
                    <th>amount</th>
                    <th>callsign</th>
                    <th>saletax</th>
                    <th>totalprice</th>
                    <th>comment</th>
                    <th>spid</th>
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
                    <td>${eintrag.articleid}</td>
                    <td>${eintrag.memberid}</td>
                    <td>${eintrag.amount}</td>
                    <td>${eintrag.callsign}</td>
                    <td>${eintrag.saletax}</td>
                    <td class="rechts">${eintrag.totalprice} €</td>
                    <td class="links">${eintrag.comment}</td>
                    <td>${eintrag.spid}</td>
                </tr>
            `;
        });
        html += "</table>";

        // Anzeige im Portal
        portalmenu2.innerHTML = "<h2 style='display: inline;'>Abrechnung</h2>";
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


        // Event Listener für Kontoausgleichs-Button
        const KontoausgleichBtn = document.getElementById("bt-Kontoausgleich");
        KontoausgleichBtn.addEventListener("click", () => {
                Kontoausgleich(datum2, abrechnung);
        });


    }

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

    // Function to append HTML content to portalInhalt
    function appendHTMLToPortalInhalt(htmlContent) {
        portalInhalt.insertAdjacentHTML("beforeend", htmlContent);
    }

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

</script>
</body>
</html>
