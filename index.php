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
    
	<div id="display">
   	
		<div id="linkeSpalte">
			
			<div id="statusfeld">
			   	 Guten Tag!
			</div>
			
			<div id="datenfeld">
                <table id="warenkorbtabelle" border="0">
					<thead></thead>
					<tbody>
                        <tr>
                            <th>Scanne ein Produkt oder lege deinen Chip auf.</th>
                        </tr>
                        <tr>
                            <th>Wenn du zuerste deinen Chip auflegst, bekommst du alle deine bisherigen Käufe angezeigt.</th>
                        </tr>
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
			    <button onclick="kundentagesübersicht();">Kunden Tagesübersicht</button>
	    	</div>
				
			<div id="summenkasten">
				 <span id=summenfeld>0.00</span>&nbsp;€
			</div>
			
		</div>
	
<script>

        const terminal = 1;
        
		let produkte = <?php echo json_encode($produkte); ?>;
		let kunden = <?php echo json_encode($kunden); ?>;

		//console.log("produkte: ", produkte);
		//console.log("kunden: ", kunden);
		
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
		
		let tastenkontrolle = function(event) {
                
            if (event.key === "Enter") {
        
        		produktprüfung(eingabe);
				
		    	kundenprüfung(eingabe);
				
		    	if(eingabe) {
			        //console.log("Kein Kunde und kein Produkt!");
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
	
function produktprüfung(EANr) {
   
        if (warenkorb.length == 0) {

            console.log("Warenkorb ist leer!");

            warenkorb2 = [];

            //warenkorbtabelle.innerHTML= "";

            tbody.innerHTML = ""; // Bestehenden Inhalt löschen
            
            thead.innerHTML = `
            <tr>
							<th class="rechts_groß" >Produkt</th>
							<th class="rechts_groß" style="width: 140px">Preis</th>
							<th style="width: 50px"></th>		
		    </tr>
             `;
        }

		let produkt = produkte.find(produkte => produkte.EAN === EANr);
		
		if (produkt) {

            console.log("Produkt gefunden: ", produkt);

			warenkorb.push(produkt);

			statusfeld.innerText = "Produkt dem Warenkorb hinzugefügt. Weitere Produkte scannen oder durch auflegen des Chips bezahlen."
			
			summe = summe + parseFloat(produkt.Preis);
			
			summenfeld.innerText = summe.toFixed(2);
			
			let zeile = tbody.insertRow();
        	let zelle1 = zeile.insertCell();
        	
        	zelle1.innerText = produkt.Bezeichnung;
        	zelle1.className = "rechts_groß";
        	
        	let zelle2 = zeile.insertCell();
        	zelle2.innerText = produkt.Preis + " €";
        	zelle2.className = "rechts_groß";
	
	        eingabe = "";
	        
		}
 	
	}
	
function kundenprüfung(KundenNr) {
		
		let kunde = kunden.find(kunden => kunden.ID === KundenNr);
		
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
		            console.log("ds: ", ds);
                    console.log("now: ", now);  
                    console.log("Datum: ", datum);
		            let ds2 = {
		                Datum: datum,
		                Zeit: zeit,
		                Terminal: terminal,
		                Kunde: kunde.ID,
		                EAN: ds.EAN,
		                Produkt: ds.Bezeichnung,
		                Kategorie: ds.Kategorie,
		                Preis: ds.Preis,
		                MwSt: ds.MwSt
		            }
		            console.log("ds2: ", ds2);
		            warenkorb2.push(ds2);
		        }

                übertragung_verkaufsliste(warenkorb2);
               
			    statusfeld.innerText = "Produkte aus dem Warenkorb wurden dem Kundenkonto von " + kunde.Name + ", " + kunde.Vorname + " übertragen.";
			    tbody.innerHTML = "";
                thead.innerHTML = "Prokukte bezahlt!";

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

	};

async function kundenkontoübersicht(KundenNr) {

document.removeEventListener("keydown", tastenkontrolle);    

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
        //console.log("Abgerufene Daten: ", data);

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
            
            //console.log("row: ", row);

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
    
    document.removeEventListener("keydown", tastenkontrolle);

    let tagessumme = 0.0;
    
    try {
        let response = await fetch("tagesabrechnung-csv.php");
        if (!response.ok) {
            throw new Error("Netzwerkantwort war nicht ok: " + response.statusText);
        }
    
        let data = await response.json();
        //console.log("Abgerufene Daten: ", data);

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
            
            //console.log("row: ", row);
            
            let kunde = kunden.find(kunden => kunden.ID === row.Kunde);
         
            tagessumme += parseFloat(row.Preis);
            
            let tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="zentriert">` + row.Terminal + `</td>
                <td class="zentriert">` + row.Zeit + `</td>
                <td>` + kunde.Name + ", " + kunde.Vorname + `</td>
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

    document.removeEventListener("keydown", tastenkontrolle);
    
    let tagessumme = 0.0;
    
    try {
        let response = await fetch("tagesabrechnung-csv.php");
            if (!response.ok) {
                throw new Error("Netzwerkantwort war nicht ok: " + response.statusText);
            }
    
        let data = await response.json();
        
        //console.log("Abgerufene Daten: ", data);

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

        //console.log("Ergebnis: ", productCounts);
        
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

    document.removeEventListener("keydown", tastenkontrolle);
    
    let tagessumme = 0.0;
    
    try {
        let response = await fetch("tagesabrechnung-csv.php");
            if (!response.ok) {
                throw new Error("Netzwerkantwort war nicht ok: " + response.statusText);
            }
    
        let data = await response.json();
        console.log("Abgerufene Daten: ", data);

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
    

        console.log(customerData);
        thead.innerHTML = "";
        tbody.innerText = ""; // Bestehenden Inhalt löschen
        
       for (const Kunde1 in customerData) {
         
            let kunde = kunden.find(kunden => kunden.ID === Kunde1);

            console.log("Kunde: ", kunde);

            let tr = document.createElement("tr");
            tr.innerHTML = `
                <td colspan="4" id="Namenfeld">` + kunde.Name + `, ` + kunde.Vorname + `</td>
            `;
            tbody.appendChild(tr);

            let kundensumme = 0.0;
            
            for (const [product, details] of Object.entries(customerData[Kunde1])) {
             
                
                let tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="zentriert">` + details.count + `</td>
                    <td class="links">` + product + `</td>
                    <td class="währung">` + details.unitPrice.toFixed(2) + ` €</td>
                    <td class="währung">` + details.totalPrice.toFixed(2) + ` €</td>
                `;
                tbody.appendChild(tr);
                kundensumme += details.totalPrice;
            }

            tagessumme += kundensumme;

            let tr2 = document.createElement("tr");
            tr2.innerHTML = `
                <td></td>
                <td></td>
                <td></td>
                <td class="währung"><b>` + kundensumme.toFixed(2) + ` €</b></td>
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
    
    //console.log("Data: ", data);

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
    </script>

</body>
</html>