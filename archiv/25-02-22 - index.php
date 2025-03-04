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
	
	<link rel="stylesheet" href="style.css">
	
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
			<p>Datenfeld</p>
   	 		<table id="warenkorb" border="1">
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
				<p>Statuszeile</p>
				<p id="warenkorbfeld">unbekannter Fehler!</p>
			</div>
			<div id="summenfeld">
				 <p>0.00 €</p>
			</div>
		</div>
		<div id="menubar">
			<p>Menubar</p>
			<button onclick="window.location.reload(true);">Abbruch</button>
			<button>Tagesabrechnung</button>
		</div>
	</div>
	
	
<script>
		let produkte = <?php echo json_encode($produkte); ?>;
		let kunden = <?php echo json_encode($kunden); ?>;
		let warenkorb = [];
		let summe = 0.0;
		
		let warenkorbfeld = document.getElementById("warenkorbfeld");
		let summenfeld = document.getElementById("summenfeld");	
		let statusfeld = document.getElementById("statusfeld");
	
		console.log("ProdukteDB: ", produkte);
		console.log("KundenDB: ", kunden);
		
		document.getElementById("warenkorbfeld").textContent += "Produkte: " + JSON.stringify(produkte, null, 2);
		document.getElementById("warenkorbfeld").textContent += "Kunden: " + JSON.stringify(kunden, null, 2);
		
		let eingabe = "";

		document.addEventListener("keydown", function(event) {



            if (event.key.length === 1) { 
				
				eingabe += event.key;
				
				console.log("Eingabe hat den Wert: " + eingabe);
				console.log("Eingabe hat eine Länge von: " + eingabe.length);

			produktprüfung(eingabe);
				
			kundenprüfung(eingabe);
				
			} 
			
			if (eingabe.length === 13) {
				console.log("Die Eingabe hat 13 erreicht und wird gelöscht");
				eingabe = "";
			}
				
	
        });
	
	function produktprüfung(x) {
		
		let produkt = produkte.find(produkte => produkte.EAN === x);
		
		if (produkt) {
			
			warenkorb.push(produkt);
			
			console.log("Warenkorb :", warenkorb);
			
			statusfeld.innerText = "Produkt dem Warenkorb hinzubefügt."

			eingabe = "";
			
			summe = summe + parseFloat(produkt.Kosten);
			
			summenfeld.innerText = summe + " €";
			
			let tbody = document.querySelector("#warenkorb tbody");
			let zeile = tbody.insertRow();
        	zeile.insertCell().innerText = produkt.Bezeichnung;
        	zeile.insertCell().innerText = produkt.Kosten;
			
		}
 	
	}
	
	function kundenprüfung(x) {
		
		let kunde = kunden.find(kunden => kunden.ID === x);
		if (kunde) {
			
			warenkorbfeld.innerText = JSON.stringify(kunde, null, 2);

			eingabe = "";
			
			statusfeld.innerText = "Produkte wurden dem Kundenkonto von " + kunde.Name + ", " + kunde.Vorname + " berechnet.";
			
		}
		console.log(kunde); 
	};
	
	async function EAN-Name(eanCode) {
        const apiUrl = `https://www.ean-search.org/api?op=barcode-lookup&ean=${eanCode}&format=json`;

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) {
              throw new Error(`Fehler: ${response.status} ${response.statusText}`);
            }

            const data = await response.json();
            if (data && data.product && data.product.name) {
                return data.product.name;
            } else {
                throw new Error('Produktname nicht gefunden.');
            }
        } catch (error) {
            console.error('Fehler beim Abrufen der Produktdaten:', error);
            return null;
        }
}
	
    </script>

</body>
</html>
