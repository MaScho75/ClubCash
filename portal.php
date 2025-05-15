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
        <li><a href="#" onclick="abrechnung()">abrechnung</a></li>
      </ul>
    </li>
 
    <li>
      <a href="#" id="MenuEinstellungen" style="display: none;">Einstellungen</a>
      <ul>
        <li><a href="#" class="disabled">Zugriff Vereinsflieger</a></li>
        <li><a href="#" onclick="Farben()">Farben</a></li>
        <li><a href="#" onclick="Programmeinstellungen()">Porgrammeinstellungen</a></li>
        <li><a href="#" class="disabled">alle Daten löschen</a></li>
      </ul>
    </li>
    <li>
      <a href="#" id="MenuDownload" style="display: none;">Download</a>
      <ul>
        <li><a href="daten/produkte.json" >Produktliste CSV</a></li>
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

    function Abrechnung() {

        portalmenu2.innerHTML = "<h2 style='display: inline;'>Abrechnung</h2>";

        let html = "";
        html += `
        <h3>1. Kasse offline stellen</h3>
        <p>Die Kasse offline stellen, um keine weiteren Verkäufe zuzulassen.</p>
        <button id="btOffline" class="kleinerBt" disabled>offline</button>
        <h3>2. Kontostande übertragen</h3>
        <p>Die Kontostände der Mitglieder in die Vereinsflieger-Datenbank übertragen.</p>
        <button id="btVFTansfer" class="kleinerBt" disabled>übertragen</button>
        <h3>Kontostände ausgleichen</h3>
        <p>Die Kontostände der Mitglieder in jeder Kategorie auf 0 € gesetzt.</p>
        <button id="btKontoausgleich" class="kleinerBt" disabled>zurücksetzen</button>
        <h3>3. Kasse online stellen</h3>
        <p>Die Kasse online stellen, um weitere Verkäufe zuzulassen.</p>
        <button id="btOnline" class="kleinerBt" disabled>online</button>

        `;

        portalInhalt.innerHTML = html;
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

        console.table(warenbestand); // Debug-Ausgabe der Warenbestand

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
                        console.log("Key:", key, "Value:", item[key]); // Debug-Ausgabe der Schlüssel und Werte
                        if (key === "Eingang") {
                            console.log("Eingang:", item[key]); // Debug-Ausgabe der Schlüssel und Werte
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
                        alert('Fehler beim JSON erstellen:', error);
                    });

            };

        } // Ende der Funktion createEditableTable
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
        fetch('config.js?v=' + Date.now()) // Browser-Caching umgehen
            .then(response => {
                if (!response.ok) {
                    throw new Error('Fehler beim Laden der Konfiguration');
                }
                return response.text();
            })
            .then(text => {

                portalmenu2.innerHTML = "<h2 style='display: inline;'>Programmeinstellungen (config.js)</h2>";
                portalInhalt.innerHTML = `
                    <pre padding: 10px; overflow-x: auto;">${escapeHtml(text)}</pre>
                `;
            })
            .catch(error => {
                portalInhalt.innerHTML = `<p style="color:red;">${error.message}</p>`;
            });
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

                fetch('JSON-schreiben.php', {
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
                    return fetch('JSON-schreiben.php', {
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
                    <td>Mitgliedsnr</td>
                    <td>${kunde.uid}</td>
                </tr>
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
            
                //if (verkauf.Datum === datum1.toISOString().split('T')[0])

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

            console.log('Produktgruppen:', produktgruppen); // Debug-Ausgabe der Produktgruppen

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
    
    function abrechnung() {
        // Zeitspanne festlegen vom 1.1. bis heute
        let startDate = jahresbeginn.toISOString().split('T')[0];
        let endDate = heute.toISOString().split('T')[0];

        // Array für die Abrechnung erstellen
        let Abrechnung = [];

        // Für jeden Kunden...
        kunden.forEach(kunde => {
            // Verkäufe des Kunden im Zeitraum filtern
            let kundenVerkäufe = verkäufe.filter(v => 
                v.Kundennummer === kunde.uid &&
                v.Datum >= startDate && 
                v.Datum <= endDate
            );

            // Nach Produktgruppen gruppieren
            let gruppenSummen = {};
            kundenVerkäufe.forEach(verkauf => {
                if (!gruppenSummen[verkauf.Kategorie]) {
                    gruppenSummen[verkauf.Kategorie] = 0;
                }
                gruppenSummen[verkauf.Kategorie] += parseFloat(verkauf.Preis);
            });

            // Abrechnungstext erstellen
            let abrechnungsText = Object.entries(gruppenSummen)
                .map(([gruppe, summe]) => `${gruppe}: ${summe.toFixed(2)}€`)
                .join(', ');

            // Zur Abrechnung hinzufügen
            Abrechnung.push({
                Kundennummer: kunde.uid,
                Vorname: kunde.firstname,
                Nachname: kunde.lastname,
                Abrechnung: abrechnungsText
            });
        });

        console.table(Abrechnung); // Debug-Ausgabe der Abrechnung

        // HTML für die Abrechnung erstellen
        let html = '<h2>Abrechnung</h2><table class="portal-table">';
        html += `
            <tr>
                <th>Kundennummer</th>
                <th class="links">Vorname</th>
                <th class="links">Nachname</th>
                <th class="links">Abrechnung</th>
            </tr>`;
        Abrechnung.forEach(eintrag => {
            html += `<tr>
                <td>${eintrag.Kundennummer}</td>
                <td class="links">${eintrag.Vorname}</td>
                <td class="links">${eintrag.Nachname}</td>
                <td class="links">${eintrag.Abrechnung}</td>
            </tr>`;
        });
        html += '</table>';
        portalInhalt.innerHTML = html;
        
    }
    
    </script>
</body>
</html>
