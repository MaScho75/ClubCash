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
	   
	    <div id="kopf">
            <img src="../CL-Logo_100.png">	   
	        <h1>Cafe Lüsse Kasse AdminTool</h1>
	    </div>
	    
		    <a href="produkte.html"><button>Produktkatalog</button></a>

	    	<button disabled onclick="">Kundenliste</button>
	        <button disabled onclick="">Verkaufsliste Kunden</button>
	        <button disabled onclick="">Kunden aus Vereinsflieger kopieren</button>
    	    <button disabled onclick="">Abrechnung per Email an alle Kunden senden</button>
    	    <button disabled onclick="">Abrechnung per Email an ausgewählten Kunden senden</button>
       	    <button disabled onclick="">Kunden aus Vereinsflieger kopieren</button>
    	    <button disabled onclick="">alle Kundenkonten zurücksetzen</button>
    	    <button disabled onclick="">Abrechnungen an Vereinslieger übertragen</button>
    	    <a href="../abrechnung"><button>Tagesabrechnung heute</button></a>
    	    <button disabled onclick="">Tagesabrechnung Datum</button>
    	    <a href="../daten/produkte.csv"><button>Produkte - csv-download</button></a>
    	    <a href="../daten/kunden.csv"><button>Kunden - csv-download</button></a>
    	    <a href="../daten/verkaufsliste.csv"><button>Verkaufsliste - csv-download</button></a>
    	    
    	    
    	    
	    
    </div>

</body>
</html>