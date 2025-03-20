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

</head>

<body>
    
	<div id="adminfenster">
	   
	    <div id="kopf" style="display: flex; align-items: center;">
            <img src="../CL-Logo_100.png" style="margin: 10px;">	   
	        <h1>Cafe Lüsse Kasse AdminTool</h1>
	    </div>
		<div id="ausgabe"  style="display: flex; flex-direction: column;">
		    <a href="produkte.html"><button>Produktkatalog</button></a>
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
			
    </div>

    <script>
        function Mitgliedsdaten_ziehen() {
			document.getElementById("ausgabe").innerHTML = "<p>Bitte warten, die Mitgliederdaten werden aus Vereinsflieger abgerufen...</p>";
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "pull_Mitgliedsdaten_Vereinsflieger.php", true); 
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("ausgabe").innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    </script>


</body>
</html>