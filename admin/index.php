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
				<button disabled onclick="">Mitgliederliste</button>
				<button disabled onclick="">Verkaufsliste Mitglieder</button>
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
				Hier kommt der Inhalt!
			</div>

		</div>
		<div id="fusszeile">
			<hr>
			<p>ClubCash - Marcel Schommer - marcel@schommer.berlin - +49 170 55 10 566</p>
		</div>
		
    </div>

    <script>
        function Mitgliedsdaten_ziehen() {
			document.getElementById("inhaltsspalte").innerHTML = "<p>Bitte warten, die Mitgliederdaten werden aus Vereinsflieger abgerufen...</p>";
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "pull_Mitgliedsdaten_Vereinsflieger.php", true); 
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("inhaltsspalte").innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }


		function produktkatalog_aufrufen() {
			document.getElementById("inhaltsspalte").innerHTML = "<iframe src='produkte.html?v=" + Date.now() + "' style='width: 100%; height: 700px'></iframe>";
		}
    </script>


</body>
</html>