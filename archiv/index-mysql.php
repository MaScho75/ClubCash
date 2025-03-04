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

	
</head>
	
<?php
// 🔹 Verbindung zur Datenbank herstellen
$host = "localhost";
$user = "d042e086";
$pass = "CLK20250220";
$dbname = "d042e086";

// Verbindung erstellen
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

/// Produkte
// 🔹 SQL-Abfrage ausführen
$sql = "SELECT * FROM Produkte";
$result = $conn->query($sql);

// 🔹 Daten in ein Array speichern
$data = [];
while ($row = $result->fetch_assoc()) {
    $produkte[] = $row;
}

/// Kunden
// 🔹 SQL-Abfrage ausführen
$sql = "SELECT * FROM Kunden";
$result = $conn->query($sql);

// 🔹 Daten in ein Array speichern
$data = [];
while ($row = $result->fetch_assoc()) {
    $kunden[] = $row;
}


// 🔹 Verbindung schließen
$conn->close();
?>	
	
<body>
    
	<div id="display">
   	
		<div id="linkeSpalte">
			
			<div id="statusfeld">
			   	 Bitte Produkt scannen oder Chip einlesen.
			</div>
			
			<div id="datenfeld">
   	 			<table id="warenkorbtabelle" border="0">
					<thead>
						<tr>
							<th class="rechts_groß" >Produkt</th>
							<th class="rechts_groß" style="width: 140px">Preis</th>
							<th style="width: 50px"></th>
							
						</tr>		
					</thead>
					<tbody>
					</tbody>		   
			    </table>
		    </div>
		
		</div>
		
		<div id="rechteSpalte">
		    
			<div id="logo">
			    <img src="CL-Logo_100.png" alt="CLK">
			</div>
			
			<div id="menubar">
		    	<button onclick="window.location.reload(true);">Abbruch</button>
			    <button onclick="tagesabrechnung();">Tagesabrechnung</button>
			    <button onclick="tageszusammenfassung();">Tageszusammenfassung</button>
			    <button>Kunden Tagesübersicht</button>
	    	</div>
				
			<div id="summenkasten">
				 <span id=summenfeld>0.00</span>&nbsp;€
			</div>
			
		</div>
	
<script>
        const terminal = 1;
		let produkte = <?php echo json_encode($produkte); ?>;
		let kunden = <?php echo json_encode($kunden); ?>;
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
		
	    console.log("ProdukteDB: ", produkte);
		console.log("KundenDB: ", kunden);
		
		
		
		let tastenkontrolle = function(event) {
                
            if (event.key === "Enter") {
                console.log("Eingabetaste wurde gedrückt!");
                produktprüfung(eingabe);
		    	kundenprüfung(eingabe);
		    	
		    	if(eingabe) {
			        console.log("Kein Kunde und kein Produkt!");
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
	
function produktprüfung(EAN_Nr) {

		let produkt = produkte.find(produkte => produkte.EAN === EAN_Nr);
		
		if (produkt) {

			warenkorb.push(produkt);
			
			console.log("Warenkorb :", warenkorb);
			
			statusfeld.innerText = "Produkt dem Warenkorb hinzubefügt."
			
			summe = summe + parseFloat(produkt.Kosten);
			
			summenfeld.innerText = summe.toFixed(2);
			
			let zeile = tbody.insertRow();
        	let zelle1 = zeile.insertCell();
        	
        	zelle1.innerText = produkt.Bezeichnung;
        	zelle1.className = "rechts_groß";
        	
        	let zelle2 = zeile.insertCell();
        	zelle2.innerText = produkt.Kosten + " €";
        	zelle2.className = "rechts_groß";
	
	        eingabe = "";
	        
		}
 	
	}
	
function kundenprüfung(KundenNr) {
		
		let kunde = kunden.find(kunden => kunden.ID === KundenNr);
		
		if (kunde) {
		    if (!summe == 0) {
		        
		        let warenkorb2 = [];

		        for (ds of warenkorb) {
		           
		            let ds2 = {
		                ...ds,
		                Terminal: terminal,
		                Kunde: kunde.ID
		            }
		            
		            warenkorb2.push(ds2);
		        }

                übertragung(warenkorb2);
            
			    statusfeld.innerText = "Produkte aus dem Warenkorb wurden dem Kundenkonto von " + kunde.Name + ", " + kunde.Vorname + " übertragen.";
			    tbody.innerText = "";
		        eingabe = "";
				warenkorb = [];
				warenkorb2 = [];
		        summe = 0;
		    }
		    else {
		        eingabe = "";
		        statusfeld.innerText = "Kontoübersicht " + kunde.Name + ", " + kunde.Vorname;
		        kundenkontoübersicht(KundenNr);
		    }
		}
		console.log(kunde); 
	};

async function kundenkontoübersicht(KundenNr) {

    let tagessumme = 0.0;
    
    try {
        //let response = await fetch(`kundenkontoübersicht.php`);
        let response = await fetch(`kundenkontoübersicht.php?id=${KundenNr}`);
        let data = await response.json();

        if (data.status !== "Erfolg") {
            console.error("Fehler beim Abrufen:", data.message);
            return;
        }

        tbody.innerText = ""; // Bestehenden Inhalt löschen
		
    	thead.innerHTML = `
            <tr>
                <th>T</th>
                <th>Datum</th>
                <th>Zeit</th>
                <th style="text-align: left;">Produkt</th>
                <th class="rechts">Preis</th>
            </tr>
        `;

        data.daten.forEach(row => {
            
            tagessumme += parseFloat(row.Preis);
            let tr = document.createElement("tr");

            tr.innerHTML = `
                <td>${row.Terminal}</td>
                <td class="zentriert">${row.Datum}</td>
                <td class="zentriert">${row.Zeit}</td>
                <td>${row.Produkt}</td>
                <td class="währung">${row.Preis} €</td>
            `;

            tbody.appendChild(tr);
        });

    } catch (error) {
        console.error("Fehler beim Laden der Daten:", error);
    }
    
    summenfeld.innerText = tagessumme.toFixed(2);
    Eingabe_Stop();
}

	
async function übertragung(produkte) {

	let response = await fetch("api.php", {
	    method: "POST",
	    headers: { "Content-Type": "application/json" },
 	   body: JSON.stringify({ empfangen: { produkte: produkte } }) // Hier wird "empfangen" hinzugefügt
	});

	let data = await response.text(); // Oder response.json(), falls die API JSON zurückgibt
		console.log("Antwort von API:", data);

}
	
async function tagesabrechnung() {
    let tagessumme = 0.0;
    try {
        let response = await fetch("tagesabrechnung.php");
        let data = await response.json();

        if (data.status !== "Erfolg") {
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

        data.daten.forEach(row => {
            
            tagessumme += parseFloat(row.Preis);
            let tr = document.createElement("tr");

            tr.innerHTML = `
                <td class="zentriert">${row.Terminal}</td>
                <td class="zentriert">${row.Zeit}</td>
                <td>${row.Kunde}</td>
                <td>${row.Produkt}</td>
                <td class="währung">${row.Preis} €</td>
            `;

            tbody.appendChild(tr);
        });

    } catch (error) {
        console.error("Fehler beim Laden der Daten:", error);
    }
    
    statusfeld.innerText = "Tagesabrechnung";
    summenfeld.innerText = tagessumme.toFixed(2);
    Eingabe_Stop();
    
}

async function tageszusammenfassung(){
    let tagessumme = 0.0;
    try {
        let response = await fetch("tageszusammenfassung.php");
        let data = await response.json();

        if (data.status !== "Erfolg") {
            console.error("Fehler beim Abrufen:", data.message);
            return;
        }

        tbody.innerText = ""; // Bestehenden Inhalt löschen
		
    	thead.innerHTML = `
            <tr>
             <th>Anzahl</th>
             <th style="text-align: left;">Bezeichnung</th>
             <th class="rechts">Preis</th>
             <th class="rechts">Summe</th>
            </tr>
        `;

        data.daten.forEach(row => {
            
            tagessumme += parseFloat(row.Summenpreis);
            let tr = document.createElement("tr");

            tr.innerHTML = `
                <td class="zentriert" style="width: 50px;">${row.Anzahl}</td>
                <td>${row.Bezeichnung}</td>
                <td class="währung">${row.Einzelpreis} €</td>
                <td class="währung">${row.Summenpreis} €</td>
            `;
            
            tbody.appendChild(tr);
        });

    } catch (error) {
        console.error("Fehler beim Laden der Daten:", error);
    }
    
    statusfeld.innerText = "Tageszusammenfassung";
    summenfeld.innerText = tagessumme.toFixed(2);
    Eingabe_Stop();
    
    
}


function Eingabe_Stop() {
    
    document.removeEventListener("keydown", tastenkontrolle);
    
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

    </script>

</body>
</html>