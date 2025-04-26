<?php
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produkte Bearbeiten & Speichern</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@latest/dist/handsontable.full.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Carlito&display=swap" rel="stylesheet">
    <style>
        #csvTable {
            width: 100%;
            height: 400px;
        }
        
        body {
            font-family: 'Carlito', sans-serif;
        }   
        
        #kopf {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 10px;
        }
        
    </style>
</head>
<body>
<p>Hinweise: Die Spalte "Sortierung" dient der Anzeige in der Preisliste.
        Zusätzlich werden die Produkte mit der Sortiernummer 1 bis 8 auf dem Startbildschirm angezeigt.
        Der Preis für das Mittagessen kann vom Café-Lüsse-Dienst selbst angepasst werden. 
        Die Sortiernummer 1 und die EAN-Nr 1 dürfen deshalb nicht verändert werden. </p>

<button onclick="saveCSV()">Speichern</button>

<div id="csvTable"></div>

<script src="https://cdn.jsdelivr.net/npm/handsontable@latest/dist/handsontable.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>

<script>
    Handsontable.helper.licenseKey = 'non-commercial-and-evaluation';
    const csvUrl = "daten/produkte.csv"; 
    let hot;

    function loadCSV() {
        fetch(csvUrl + "?nocache=" + new Date().getTime()) // Cache umgehen
            .then(response => response.text())
            .then(csv => {
                const parsedData = Papa.parse(csv, { header: true, skipEmptyLines: true, delimiter: ";" }).data;
                renderHandsontable(parsedData);
            });
    }

    function renderHandsontable(data) {
        const container = document.getElementById('csvTable');
        hot = new Handsontable(container, {
            licenseKey: 'non-commercial-and-evaluation',
            data: data,
            colHeaders: Object.keys(data[0]),  
            rowHeaders: true,  
            contextMenu: true,  
            manualColumnResize: true,  
            manualRowResize: true,  
            filters: true,  
            columnSorting: true,
            dropdownMenu: true,  
            minSpareRows: 1  
        });
    }

    function saveCSV() {
        const colHeaders = hot.getColHeader();  
        const data = hot.getData();

        const csv = Papa.unparse({
            fields: colHeaders,  
            data: data           
        }, {
            delimiter: ";"  // Semikolon als Trennzeichen
        });

        fetch('kasse/save_produkte_csv.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ csvData: csv })
        })
        .then(response => response.text())
        .then(result => {
            alert(result);
            loadCSV(); // Datei nach dem Speichern neu laden
        })
        .catch(error => console.error('Fehler:', error));
    }

    loadCSV(); // Initial CSV laden
</script>

</body>
</html>
