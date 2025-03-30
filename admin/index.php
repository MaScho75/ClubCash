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
	
	<link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
	
	<link href="https://fonts.googleapis.com/css2?family=Carlito&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="../farben.css?v=<?php echo time(); ?>">

</head>

<body>
    
	<div id="adminfenster">
	   
	    <div id="kopf" style="display: flex; align-items: center;">
            <img src="../grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px;  margin: 30px;">	   
	        <h1>ClubCash AdminTool</h1>
	    </div>
		<div id="mittelteil">
			<div class="menuspalte">
				<button onclick="produktkatalog_aufrufen();">Produktkatalog</button></a>
				<button onclick="Mitgliederdaten_anzeigen ()">Mitgliederliste</button>
				<button onclick="verkaufsliste()">Verkaufsliste</button>
				<button onclick="Mitgliedsdaten_ziehen();">Mitglieder aus Vereinsflieger importieren</button>
				<button disabled onclick="">Abrechnung per Email an alle Mitglieder senden</button>
				<button disabled onclick="">Abrechnung per Email an ein ausgewähltes Mitglied senden</button>
				<button disabled onclick="">alle Mitgliedskonten zurücksetzen</button>
				<button disabled onclick="">Abrechnungen an Vereinslieger exportieren</button>
				<a href="../abrechnung"><button>Tagesabrechnung heute</button></a>
				<button disabled onclick="">Tagesabrechnung Datum</button>
				<a href="../daten/produkte.csv"><button>Produkte - csv-download</button></a>
				<a href="../daten/kunden.JSON"><button>Kunden - JSON-download</button></a>
				<a href="../daten/verkaufsliste.csv"><button>Verkaufsliste - csv-download</button></a>
			</div>
			<div id="inhaltsspalte">
				<h1>Willkommen im Adminmodul von ClubCash</h1>
				<h2>Willkommen im Adminmodul von ClubCash</h2>
				<h3>Willkommen im Adminmodul von ClubCash</h3>
				<h4>Willkommen im Adminmodul von ClubCash</h4>
				<h5>Willkommen im Adminmodul von ClubCash</h5>
				<h6>Willkommen im Adminmodul von ClubCash</h6>
				<p>Willkommen im Adminmodul von ClubCash</p>
				
			</div>

		</div>
		<div id="fusszeile">
			<hr>
			<p>ClubCash - Marcel Schommer - marcel@schommer.berlin - +49 170 55 10 566</p>
		</div>
		
    </div>

    <script>

		const inhaltsspalte=document.getElementById("inhaltsspalte");

		function Mitgliederdaten_anzeigen() {
		
			fetch('../daten/kunden.json')
				.then(response => response.json())
				.then(data => {
					const kunden = data;
					console.log('Kunden geladen:', kunden);
					
					let html = '<h2>Mitgliederliste</h2><table border="1">';
					html += '<tr><th>ID</th><th>Vorname</th><th>Nachname</th><th>Email</th><th>Schlüssel</th></tr>';

					kunden.forEach(kunde => {
						html += `<tr>
							<td>${kunde.uid}</td>
							<td>${kunde.firstname}</td>
							<td>${kunde.lastname}</td>
							<td>${kunde.email}</td>
							<td>${kunde.key2designation}</td>
						</tr>`;
					});

					html += '</table>';
					inhaltsspalte.innerHTML = html;
				})
				.catch(error => {
					console.error('Fehler beim Laden der Kunden:', error);
					inhaltsspalte.innerHTML = '<p>Fehler beim Laden der Mitgliederdaten</p>';
				});

		}	

        function Mitgliedsdaten_ziehen() {
			inhaltsspalte.innerHTML = "<p>Bitte warten, die Mitgliederdaten werden aus Vereinsflieger abgerufen...</p>";
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "pull_Mitgliedsdaten_Vereinsflieger.php", true); 
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    inhaltsspalte.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }


		function produktkatalog_aufrufen() {
			inhaltsspalte.innerHTML = "<h2>Produktkatalog</h2><iframe src='produkte.html?v=" + Date.now() + "' style='width: 100%; height: 700px'></iframe>";
		}

		function verkaufsliste() {
			inhaltsspalte.innerHTML ="<h2>Verkaufsliste</h2><iframe src='verkaeufe.html?v=" + Date.now() + "' style='width: 100%; height: 700px'></iframe>";
		}

    </script>



</body>
</html>