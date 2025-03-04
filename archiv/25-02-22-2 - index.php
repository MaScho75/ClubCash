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
	
	<link rel="stylesheet"  href="style.css">
	
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
   		<div id="überschrift">
			<h1>Café Lüsse Kasse</h1>
		</div>
		<div id="datenfeld">
   	 		<table id="warenkorbtabelle" border="1">
				<thead>
					<tr>
						<th>Produkt</th>
						<th>Preis</th>
					</tr>		
				</thead>
				<tbody>
				</tbody>	
					   
			</table>
		</div>
		<div id="fußzeile">
			<div id="statusfeld">
			    Bitte Produkt scannen oder Chip einlesen.
			</div>
			<div id="summenfeld">
				 0.00 €
			</div>
		</div>
		<div id="menubar">
			<button onclick="window.location.reload(true);">Abbruch</button>
			<button>Tagesabrechnung</button>
		</div>
	</div>
	
	
<script>
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
		
	    console.log("ProdukteDB: ", produkte);
		console.log("KundenDB: ", kunden);
		
		document.addEventListener("keydown", function(event) {
                
            if (event.key === "Enter") {
                console.log("Eingabetaste wurde gedrückt!");
                produktprüfung(eingabe);
		    	kundenprüfung(eingabe);
		    	
		    	if(eingabe) {
			        console.log("Kein Kunde und kein Produkt!");
			        statusfeld.innerText = "Kein Produkt und kein Kunde erkannt."
                eingabe = "";    
                }    
			} 
			
            if (event.key.length === 1) {
				eingabe += event.key;
				console.log("Eingabe hat den Wert: " + eingabe);
			} 

        });
	
	function produktprüfung(EAN_Nr) {

		let produkt = produkte.find(produkte => produkte.EAN === EAN_Nr);
		
		if (produkt) {
			
			warenkorb.push(produkt);
			
			console.log("Warenkorb :", warenkorb);
			
			statusfeld.innerText = "Produkt dem Warenkorb hinzubefügt."
			
			summe = summe + parseFloat(produkt.Kosten);
			
			summenfeld.innerText = summe.toFixed(2) + " €";
			
			let zeile = tbody.insertRow();
        	zeile.insertCell().innerText = produkt.Bezeichnung;
        	zeile.insertCell().innerText = produkt.Kosten;
	
	        eingabe = "";
	        
		}
 	
	}
	
	function kundenprüfung(KundenNr) {
		
		let kunde = kunden.find(kunden => kunden.ID === KundenNr);
		
		if (kunde) {
		    if (!summe == 0) {
			    statusfeld.innerText = "Produkte aus dem Warenkorb wurden dem Kundenkonto von " + kunde.Name + ", " + kunde.Vorname + " übertragen.";
			    tbody.innerText = "";
		        eingabe = "";
		        summe = 0;
		    }
		    else {
		        statusfeld.innerText = "Es befinden sich keine Produkte im Warenkorb von " + kunde.Name + ", " + kunde.Vorname + ".";
		        eingabe = "";
		    }
		}
		console.log(kunde); 
	};
	
    </script>

</body>
</html>