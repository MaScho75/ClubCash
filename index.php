<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Zeichensatz auf UTF-8 setzen -->
    <meta charset="UTF-8">

    <!-- Skalierbarkeit für mobile Geräte sicherstellen -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cafè Lüsse Kasse</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
	
	<link href="https://fonts.googleapis.com/css2?family=Carlito&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
	
<?php

// Mitgliederdaten laden
$jsonKundenDatei = file_get_contents("daten/kunden.json");
$jsonKundenDaten = json_decode($jsonKundenDatei, true); // true gibt ein assoziatives Array zurück

/*
// csv kunden laden

$csvDatei = "daten/kunden.csv"; // Name der CSV-Datei
$kunden = [];

if (($handle = fopen($csvDatei, "r")) !== FALSE) {
    $header = fgetcsv($handle, 1000, ";"); // Erste Zeile als Header lesen (Spaltennamen)

    while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (count($row) == count($header)) { // Nur Zeilen mit vollständigen Werten verarbeiten
            $kunden[] = array_combine($header, $row); // Header mit Werten kombinieren
        }
    }
    fclose($handle);
}
*/

// csv produkte laden

$csvDatei = "daten/produkte.csv"; // Name der CSV-Datei
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

?>	
	
<body>
    <div id="Testhinweis" style=" 
    position: absolute;
    top: 200px;
    left: 50px;
    text-align: center;
    font-size: 23px;
    color: rgba(255, 0, 0, 0.6);
    font-weight: bold;">  
        <p>Achtung: Diese Kasse ist im Testmodus!<br>
        Es werden keine echten Transaktionen durchgeführt!</p>
    </div>
	<div id="display">
   	
		<div id="linkeSpalte">
			
			<div id="statusfeld">
			   	 Guten Tag!
			</div>
			
			<div id="datenfeld">
                <table id="warenkorbtabelle" border="0">
					<thead></thead>
					<tbody>
                        <tr><td class="zentriert" style="padding: 20px;">Bitte Produkt scannen oder Chip auflegen.</td></tr>
                        <tr><td class="zentriert"><img src="FCC-logo.png" style="width: 200px;"></td></tr>    
                        <tr>
                            <td class="zentriert">Mittagessen: 
                                <span id=Mittagessenpreis>x.xx</span> € 
                            </td>
                        </tr>
                        <tr><td class="zentriert"><button>Preisliste</button></td></tr>
					</tbody>
					
			    </table>
			    
		    </div>
		
		</div>
		
		<div id="rechteSpalte">
		    
			<div id="logo">
			    <img src="CL-Logo_100.png" alt="CLK">
			</div>
			
			<div id="menubar">
		    	<button onclick="info()">Info</button>
                <button onclick="window.location.reload(true);">Abbruch</button>
			    <button id="Bt_tagesabrechnung" onclick="tagesabrechnung();">Tagesabrechnung</button>
			    <button id="Bt_tageszusammenfassung" onclick="tageszusammenfassung();">Tageszusammenfassung</button>
			    <button id="Bt_kundentagesübersicht" onclick="kundentagesübersicht();">Kunden Tagesübersicht</button>
			    <button id="Bt_sonderangebote" onclick="sonderangebote();">Produkte</button>
			    <button id="Bt_manuelleBuchung" onclick="manuell();">Direktbuchung</button>
                
	    	</div>
				
			<div id="summenkasten">
				 <span id=summenfeld>0.00</span>&nbsp;€
			</div>
			
		</div>
		<div id=produktfenster>
		    <h1>Produktauswahl</h1>
            <button id="bt_x" onclick="produktfenster.style.display = 'none';">X</button>  
        </div>
	    
	    <div id=tastatur>
	        <button onclick="meingabe(1)">1</button>
	        <button onclick="meingabe(2)">2</button>
	        <button onclick="meingabe(3)">3</button>
	        <button onclick="meingabe(4)">4</button>
	        <button onclick="meingabe(5)">5</button>
	        <button onclick="meingabe(6)">6</button>
	        <button onclick="meingabe(7)">7</button>
	        <button onclick="meingabe(8)">8</button>
	        <button onclick="meingabe(9)">9</button>
	        <button onclick="meingabe(0)">0</button>
	        <button id="BT_Eingabe" style="background-color: RGB(227, 180, 14); border: 1px solid black;" onclick="meingabe('E')">E</button>
            <button style="background-color: RGB(108, 159, 56); border: 1px solid black;" onclick="tastatur.style.display = 'none';">X</button>
	        
<script>

        //Textwarnung blinken lassen
        function blinker() {
            $('#Testhinweis').fadeOut(2000);
            $('#Testhinweis').fadeIn(2000);
        }
        
       //setInterval(blinker, 700); //Textwarnung blinken lassen

		fetch('backup.php') //Backup prüfen und kopieren
	
        //Terminal ermitteln aus der URL https://host/index.html?zahl=42
        const urlParams = new URLSearchParams(window.location.search);
        let terminal = urlParams.get("terminal"); // Holt den Wert von von "terminal" aus der URL

        if (terminal == null) {
            terminal = "X"; // Wenn kein "Terminal" in der URL, dann X
        }
     
		let produkte = <?php echo json_encode($produkte); ?>;
		
        let kunden = <?php echo json_encode($jsonKundenDaten); ?>;

		let warenkorb = [];
		let summe = 0.00;
		let eingabe = "";
		let datenfeld = document.getElementById("datenfeld");
		let warenkorbtabelle = document.getElementById("warenkorbtabelle");
		let tbody = document.querySelector("#warenkorbtabelle tbody");
		let statusfeld = document.getElementById("statusfeld");
		let summenfeld = document.getElementById("summenfeld");
		let menubar = document.getElementById("menubar");
		let thead = document.querySelector("#warenkorbtabelle thead");
		let produktfenster = document.getElementById("produktfenster");
		let tastatur = document.getElementById("tastatur");
		let eingabestring = "";
		
		let Bt_sonderangebote = document.getElementById("Bt_sonderangebote");
		let Bt_manuelleBuchung = document.getElementById("Bt_manuelleBuchung");

        let mittagessen = produkte.find(produkte => produkte.EAN === "1");
        document.getElementById("Mittagessenpreis").innerText = mittagessen.Preis;
		
		let tastenkontrolle = function(event) {
                
            if (event.key === "Enter") {
        
        		produktprüfung(eingabe);
				
		    	kundenprüfung(eingabe);
				
		    	if(eingabe) {
			        statusfeld.innerText = "Kein Produkt und kein Kunde erkannt."
			        Fehlerton();
                eingabe = "";    
                }    
			} 
			
            if (event.key.length === 1) {
				eingabe += event.key;
				console.log("Eingabe hat den Wert: " + eingabe);
			} 
        }
        
        document.addEventListener("keydown", tastenkontrolle);

//Produktauswahl Fenster mit Buttons erstellen

produkte.forEach(produkt => {
    if (parseInt(produkt.EAN) < 22) {
        let button = document.createElement("button");
        button.innerText = produkt.EAN + " - " + produkt.Bezeichnung + " - " + produkt.Preis + " €";
        button.className = "bt_produkt";
        button.onclick = function() {
            produktfenster.style.display = "none";
            produktprüfung(produkt.EAN);
        }
        produktfenster.appendChild(button);
    }
});

	
function produktprüfung(EANr) {

		let produkt = produkte.find(produkte => produkte.EAN === EANr);
		
		if (produkt) {          
            warenkorbüberschrift(); 
			warenkorb.push(produkt);
            warenkorbaddition(produkt);
	        eingabe = "";
		}
	}
	
function kundenprüfung(KundenNr) {
		
		let kunde = kunden.find(kunden => kunden.key2designation === KundenNr); // key2designation ist die Kundennummer
		
		if (kunde) {
		    if (!summe == 0) {
		        
		        let warenkorb2 = [];
                let now = new Date();     
                // Manuelles Erstellen des Datums im Format YYYY-MM-DD
                let year = now.getFullYear();
                let month = String(now.getMonth() + 1).padStart(2, '0');  // Monat ist 0-basiert, daher +1
                let day = String(now.getDate()).padStart(2, '0');  // Den Tag immer auf 2 Stellen auffüllen
                let datum = `${year}-${month}-${day}`;
		        let zeit =  now.toTimeString().split(" ")[0].slice(0, 5);

		        for (ds of warenkorb) {
		            let ds2 = {
		                Datum: datum,
		                Zeit: zeit,
		                Terminal: terminal,
		                Kunde: kunde.key2designation,
		                EAN: ds.EAN,
		                Produkt: ds.Bezeichnung,
		                Kategorie: ds.Kategorie,
		                Preis: ds.Preis,
		                MwSt: ds.MwSt
		            }
		            warenkorb2.push(ds2);
		        }

                übertragung_verkaufsliste(warenkorb2);
               
			    statusfeld.innerText = "Prokukte bezahlt!";
			    tbody.innerHTML = `
			        <tr>
			            <td class="zentriert">
			                <p>Alle Produkte aus dem Warenkorb
			                <br>wurden dem Kundenkonto von</p>
			                <h1><b>` + kunde.lastname + `, ` + kunde.firstname + `</b></h1>
			                <p>übertragen.</p>
			            </td>
			        </tr>       
			     `;
                
                thead.innerHTML = "";

		        eingabe = "";
				warenkorb = [];
				warenkorb2 = [];
		        summe = 0;
		    }
		    else {
		        eingabe = "";
		        statusfeld.innerText = "Kontoübersicht " + kunde.lastname + ", " + kunde.firstname;
		        kundenkontoübersicht(KundenNr);
		    }
		}
	}

async function kundenkontoübersicht(KundenNr) {

eingabestopp();

let kontosumme = 0.0;

    try {
  
        let response = await fetch("kundenkontoübersicht-cvs.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(KundenNr)
        });
        
        if (!response.ok) {
            throw new Error("Netzwerkantwort war nicht ok: " + response.statusText);
        }
    
        let data = await response.json();
   
        if (data.status !== "success") {
            console.error("Fehler beim Abrufen:", data.message);
            return;
        }

        tbody.innerText = ""; // Bestehenden Inhalt löschen
		
        thead.innerHTML = `
            <tr>
            <th>Datum</th>
            <th>Zeit</th>
            <th style="text-align: left;">T</th>
            <th style="text-align: left;">Produkt</th>
             <th class="rechts">Preis</th>
             </tr>
             `;
        
        data.data.forEach(row => {

            kontosumme += parseFloat(row.Preis);
            
            let tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="zentriert">` + row.Datum+ `</td>
                <td class="zentriert">` + row.Zeit + `</td>
                <td class="zentriert">` + row.Terminal + `</td>
                <td>` + row.Produkt + `</td>
                <td class="währung">` + row.Preis + ` €</td>
            `;
            tbody.appendChild(tr);
          
        });

        summenfeld.innerText = kontosumme.toFixed(2);
    
        warenkorb = [];

    } catch (error) {
        console.error("Fehler beim Laden der Daten:", error);
    }
}

async function tagesabrechnung() {
    
    eingabestopp();

    let tagessumme = 0.0;
    
    try {
        let response = await fetch("tagesabrechnung-csv.php");
        if (!response.ok) {
            throw new Error("Netzwerkantwort war nicht ok: " + response.statusText);
        }
    
        let data = await response.json();

        if (data.status !== "success") {
            console.error("Fehler beim Abrufen:", data.message);
            return;
        }

        tbody.innerText = ""; // Bestehenden Inhalt löschen
		
        thead.innerHTML = `
            <tr>
            <th>T</th>
            <th>Zeit</th>
            <th style="text-align: left;">Kunde</th>
            <th style="text-align: left;">Produkt</th>
            <th class="rechts">Preis</th>
            </tr>
            `;
        
        data.data.forEach(row => {

            let kunde = kunden.find(kunden => kunden.key2designation === row.Kunde);
         
            tagessumme += parseFloat(row.Preis);
            
            let tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="zentriert">` + row.Terminal + `</td>
                <td class="zentriert">` + row.Zeit + `</td>
                <td>` + kunde.lastname + ", " + kunde.firstname + `</td>
                <td>` + row.Produkt + `</td>
                <td class="währung">` + row.Preis + ` €</td>
            `;
            tbody.appendChild(tr);
     
            
        });
    
        statusfeld.innerText = "Tagesabrechnung";
    
        summenfeld.innerText = tagessumme.toFixed(2);
    
        warenkorb = [];

    } catch (error) {
        console.error("Fehler beim Laden der Daten:", error);
        statusfeld.innerText = "Fehler beim Laden der Daten:", error;
    }
}

async function tageszusammenfassung() {

    eingabestopp();
    
    let tagessumme = 0.0;
    
    try {
        let response = await fetch("tagesabrechnung-csv.php");
            if (!response.ok) {
                throw new Error("Netzwerkantwort war nicht ok: " + response.statusText);
            }
    
        let data = await response.json();

        if (data.status !== "success") {
            console.error("Fehler beim Abrufen:", data.message);
            return;
        }
       
        const productCounts = data.data.reduce((acc, row) => {
            
            const product = row.Produkt;  // Produktname
            const price = parseFloat(row.Preis); // Preis

            if (acc[product]) {
                acc[product].count += 1;  // Falls das Produkt schon im Objekt ist, erhöhe die Zählung
                acc[product].totalPrice += price;  // und addiere den Preis

            } else {
                acc[product] = {
                    count: 1,        // Erstes Vorkommen des Produkts
                    unitPrice: price,  // Einzelpreis
                    totalPrice: price  // Summenpreis (zu Beginn gleich dem Einzelpreis) erste Mal ist, setze den Zähler auf 1
                };
            }
            return acc;
        }, {});

        tbody.innerText = ""; // Bestehenden Inhalt löschen
		
        thead.innerHTML = `
            <tr>
            <th>Anzahl</th>
            <th class="links">Produkt</th>   
            <th class="rechts">Einzelpreis</th>
            <th class="rechts">Summe</th>
             </tr>
             `;
        
        for (const [product, details] of Object.entries(productCounts)) {
            
            let tr = document.createElement("tr");
            
            if (product == "Direktbuchung") {
                tr.innerHTML = `
                <td class="zentriert"> ${details.count} </td>
                <td> ${product} </td>
                <td class="währung"> * </td>
                <td class="währung"> ${details.totalPrice.toFixed(2)} €</td>
                `;
                tbody.appendChild(tr);
                
                tagessumme += details.totalPrice;
                
                continue;
            }
            
            tr.innerHTML = `
                <td class="zentriert"> ${details.count} </td>
                <td> ${product} </td>
                <td class="währung"> ${details.unitPrice.toFixed(2)} €</td>
                <td class="währung"> ${details.totalPrice.toFixed(2)} €</td>
            `;
            tbody.appendChild(tr);
        
            tagessumme += details.totalPrice;

    }
       
        statusfeld.innerText = "Tageszusammenfassung";
        summenfeld.innerText = tagessumme.toFixed(2);
        warenkorb = [];

    } catch (error) {
        console.error("Fehler beim Laden der Daten:", error);
        statusfeld.innerText = "Fehler beim Laden der Daten:", error;
    }
}

async function kundentagesübersicht() {

    eingabestopp();
    
    let tagessumme = 0.0;
    
    try {
        let response = await fetch("tagesabrechnung-csv.php");
            if (!response.ok) {
                throw new Error("Netzwerkantwort war nicht ok: " + response.statusText);
            }
    
        let data = await response.json();

        if (data.status !== "success") {
            console.error("Fehler beim Abrufen:", data.message);
            return;
        }
       
        const sortedByCustomer = data.data.sort((a, b) => a.Kunde.localeCompare(b.Kunde));

        // Produkte zählen, Einzelpreis und Gesamtsumme berechnen
        const customerData = sortedByCustomer.reduce((acc, row) => {
            const customer = row.Kunde; // Kunde
            const product = row.Produkt; // Produkt
            const price = parseFloat(row.Preis); // Preis als Zahl

            // Wenn der Kunde noch nicht im Accumulator ist, hinzufügen
            if (!acc[customer]) {
                acc[customer] = {};
            }

            // Wenn das Produkt noch nicht für diesen Kunden existiert
             if (!acc[customer][product]) {
                acc[customer][product] = {
                count: 0,
                unitPrice: price,
                totalPrice: 0
                };
            }

            // Produktanzahl erhöhen und Gesamtsumme berechnen
            acc[customer][product].count += 1;
            acc[customer][product].totalPrice += price;

            return acc;

        }, {});
    
        thead.innerHTML = "";
        tbody.innerText = ""; // Bestehenden Inhalt löschen
        
       for (const Kunde1 in customerData) {
         
            let kunde = kunden.find(kunden => kunden.key2designation === Kunde1);

            let tr = document.createElement("tr");
            tr.innerHTML = `
                <td colspan="4" id="Namenfeld">` + kunde.lastname + `, ` + kunde.firstname + `</td>
            `;
            tbody.appendChild(tr);

            let kundensumme = 0.0;
            
            for (const [product, details] of Object.entries(customerData[Kunde1])) {
             
                
                let tr = document.createElement("tr");
                
                if (product == "Direktbuchung") {
                  tr.innerHTML = `
                    <td class="zentriert">` + details.count + `</td>
                    <td class="links">` + product + `</td>
                    <td class="währung">*</td>
                    <td class="währung">` + details.totalPrice.toFixed(2) + ` €</td>
                  `;  
                }
                
                else { 
                  tr.innerHTML = `
                    <td class="zentriert">` + details.count + `</td>
                    <td class="links">` + product + `</td>
                    <td class="währung">` + details.unitPrice.toFixed(2) + ` €</td>
                    <td class="währung">` + details.totalPrice.toFixed(2) + ` €</td>
                  `;
                }
                
                tbody.appendChild(tr);
                kundensumme += details.totalPrice;
            }

            tagessumme += kundensumme;

            let tr2 = document.createElement("tr");
            tr2.innerHTML = `
                <td></td>
                <td></td>
                <td></td>
                <td class="währung" style="padding-bottom: 10px;"><b>` + kundensumme.toFixed(2) + ` €</b></td>
            `;
            tbody.appendChild(tr2); 
            
        }
         
        statusfeld.innerText = "Kunden Tagesübersicht";
        summenfeld.innerText = tagessumme.toFixed(2);
        warenkorb = [];

    } catch (error) {
        console.error("Fehler beim Laden der Daten:", error);
        statusfeld.innerText = "Fehler beim Laden der Daten:", error;
    }
}
    
function Fehlerton() {
    
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.type = 'square'; // Typ des Tons, ein Rechteckton (für Fehlerton)
    oscillator.frequency.setValueAtTime(440, audioContext.currentTime); // Frequenz, hier 440 Hz (A4)
    oscillator.start();

    // Stoppe den Ton nach 0.3 Sekunden
    setTimeout(() => {
        oscillator.stop();
    }, 300);
}

async function übertragung_verkaufsliste(data) {

    fetch("verkaufsliste-api.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server-Fehler: ${response.status}`);
        }
        return response.text(); // Erst als Text lesen
    })
    .then(text => {
        try {
            return JSON.parse(text); // Dann als JSON parsen
        } catch (error) {
            throw new Error("Ungültige JSON-Antwort: " + text);
        }
    })
    .then(result => console.log(result))
    .catch(error => console.error("Fehler:", error));
}

function sonderangebote() {
    produktfenster.style.display = "flow";
}

function manuell() {
    tastatur.style.display = "block";
    eingabestring = "";
}

function meingabe(x) {
    if (x == "E") {
        let manuelles_Produkt = {
            EAN: 9990000000000,
            Bezeichnung: "Direktbuchung",
            Preis: eingabestring/100,
            MwSt: 19,
            Kategorie: "Direktbuchung"
        }
        tastatur.style.display = "none";
        warenkorbüberschrift();
        warenkorb.push(manuelles_Produkt);
        warenkorbaddition(manuelles_Produkt);
        return;
    }
    eingabestring += x;
    summenfeld.innerText = (eingabestring/100).toFixed(2);
}

function eingabestopp() {
    document.removeEventListener("keydown", tastenkontrolle);
    Bt_sonderangebote.style.display = 'none';
	Bt_manuelleBuchung.style.display = 'none';
}


function warenkorbüberschrift() {
    
    if (warenkorb.length == 0) {
            warenkorb2 = [];
            tbody.innerHTML = ""; // Bestehenden Inhalt löschen
            thead.innerHTML = `
                <tr>
				    <th class="rechts_groß" >Produkt</th>
					<th class="rechts_groß" style="width: 140px">Preis</th>
					<th style="width: 50px"></th>		
		        </tr>
             `;
        }
}

function warenkorbaddition(produkt) {
    			
    		statusfeld.innerText = "Produkt dem Warenkorb hinzugefügt."
			
			summe = summe + parseFloat(produkt.Preis);
			
			summenfeld.innerText = summe.toFixed(2);
			
			let zeile = tbody.insertRow();
        	let zelle1 = zeile.insertCell();
        	
        	zelle1.innerText = produkt.Bezeichnung;
        	zelle1.className = "rechts_groß";
        	
        	let zelle2 = zeile.insertCell();
        	zelle2.innerText = produkt.Preis + " €";
        	zelle2.className = "rechts_groß";
}

function sonderangebot(EANr) {
    produktfenster.style.display = 'none';
    produktprüfung(EANr);
}

function info() {
    statusfeld.innerText = "Information zum Kassensystem";
    $("#datenfeld").load("info.html");

    Bt_tagesabrechnung.style.display = 'none';
	Bt_tageszusammenfassung.style.display = 'none';
    Bt_kundentagesübersicht.style.display = 'none';
	Bt_manuelleBuchung.style.display = 'none';
    Bt_sonderangebote.style.display = 'none';

}

</script>

</body>
</html>