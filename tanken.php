<?php

/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * ClubCash is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with ClubCash. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Copies a directory and its contents recursively
 */

// frage die datei "kunden.json" ab und speichere nur "uid" und "firstname" und "lastname" und "schlüssel" als php code 
$kunden = json_decode(file_get_contents('daten/kunden.json'), true);
$kundenListe = array_map(function($kunde) {
    return [
        'uid' => $kunde['uid'],
        'firstname' => $kunde['firstname'],
        'lastname' => $kunde['lastname'],
        'schlüssel' => $kunde['schlüssel']
    ];
}, $kunden);

// frage die Datei "produkte.json" und speiche alle Produkte ohne filter 

$produkte = json_decode(file_get_contents('daten/produkte.json'), true);
// speichere alle dAtensätze in einem Array ist einen positiven Zählerstand haben und nicht "undefined" sind, also nur die Produkte, die tatsächlich getankt werden können. Alle anderen Produkte werden ignoriert.
$produkteListe = is_array($produkte) ? array_values(array_filter($produkte, function($produkt) {
    return isset($produkt['Zählerstand']) && $produkt['Zählerstand'] > 0 && $produkt['Zählerstand'] !== "undefined";
})) : [];
$selectedTank = $_POST['tank'] ?? '';
 
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Zeichensatz auf UTF-8 setzen -->
    <meta charset="UTF-8">

    <!-- Skalierbarkeit für mobile Geräte sicherstellen -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ClubCash Tankstelle</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
	
	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	
</head>

<body>
    <form id="tanken-form" class="tanken-form">

        <h1>ClubCash Tankstelle</h1>
        
        <p><b>Kunde</b></p>
        <input type="text" id="kundenid_input" name="kundenid" placeholder="ChipNr"  required>
        <p id="KundenName">unbekannt</p>

        <label for="tank">Tank</label>
        <select id="tank" name="tank" required>
            <option id="sorte" value=""></option>
        </select>

        <p> <span id="Literpreis">0.00</span> €/Liter</p>
    
        <label for="zählerstand_alt">Zählerstand alt</label>
        <input type="number" id="zählerstand_alt" name="zählerstand_alt" step="0" required>
        
        <label for="zählerstand_umpumpen">Zählerstand nach Umpumpen</label>
        <input type="number" id="zählerstand_umpumpen" name="zählerstand_umpumpen" step="0" required>
        
        <label for="zählerstand_neu">Zählerstand nach Tanken</label>
        <input type="number" id="zählerstand_neu" name="zählerstand_neu" step="0" required>
                    
        <button type="button" style="background-color: var(--primary-color); color: var(--text-color-dark);" id="berechnen">berechnen</button>
    
        <button type="button" style="background-color: var(--error-color); color: var(--text-color-light);" id="abbrechen" onclick="Abbrechen();">abbrechen</button>

    </form>
    <div class="tanken-form" id="ergebnis"></div>
    

</body>
</html>

<script>

    // Daten aus PHP in JavaScript übergeben
    const kunden = <?php echo json_encode($kundenListe); ?>;
    const produkte = <?php echo json_encode($produkteListe); ?>;
    let selectedTank;
    
    let kundenname = 'unbekannt';

    // KundenID ermitteln aus der URL https://host/index.html?zahl=42
    const urlParams = new URLSearchParams(window.location.search);
    // Holt den Wert von "kundenid" aus der URL
    const KundenID = urlParams.get('kundenid');
    const kasse = urlParams.get('kasse');

    console.log('KundenID aus URL:', KundenID);
    console.log('Kasse aus URL:', kasse);

    function Kundenprüfung(chipId) {
        const kunde = kunden.find(k => k.schlüssel === chipId);
        if (kunde) {
            kundenname = `${kunde.firstname} ${kunde.lastname}`;
            document.getElementById('KundenName').textContent = kundenname;
        } else {
            kundenname = 'unbekannt';
            document.getElementById('KundenName').textContent = kundenname;
        }
    }

    if (KundenID) {
        const kundenInput = document.getElementById('kundenid_input');
        kundenInput.value = KundenID;
        Kundenprüfung(KundenID);
    }
    
    //Sorte auswählen und Literpreis anzeigen
    const tankSelect = document.getElementById('tank');
    produkte.forEach(produkt => {
        const option = document.createElement('option');
        option.value = produkt.Bezeichnung;
        option.textContent = produkt.Bezeichnung;
        tankSelect.appendChild(option);
    });
    tankSelect.addEventListener('change', function() {
        const selectedSorte = this.value;
        const selectedProdukt = produkte.find(produkt => produkt.Bezeichnung === selectedSorte);
        selectedTank = selectedProdukt;
        if (selectedProdukt) {
            const rawPreis = selectedProdukt.Preis ?? selectedProdukt.preis ?? '';
            const preis = Number.parseFloat(String(rawPreis).replace(',', '.'));
            if (Number.isFinite(preis)) {
                document.getElementById('Literpreis').textContent = preis.toFixed(2);
                document.getElementById('zählerstand_alt').value = selectedProdukt.Zählerstand;
                document.getElementById('zählerstand_umpumpen').value = selectedProdukt.Zählerstand;
                document.getElementById('zählerstand_neu').value = selectedProdukt.Zählerstand;
            } else {
                document.getElementById('Literpreis').textContent = '';
                document.getElementById('zählerstand_alt').value = '';
                document.getElementById('zählerstand_umpumpen').value = '';
                document.getElementById('zählerstand_neu').value = '';
            }
        }
    });

    const ID_Änderung = document.getElementById('kundenid_input');
    ID_Änderung.addEventListener('input', function() {
        Kundenprüfung(this.value.trim());
    });

    const form_berechnung = document.getElementById('tanken-form');

    const berechnenButton = document.getElementById('berechnen');

    const ErbenisDiv = document.getElementById('ergebnis');

    berechnenButton.addEventListener('click', function() {
        const aktuelleKundenID = document.getElementById('kundenid_input').value.trim();
        const literpreis = parseFloat(document.getElementById('Literpreis').textContent);
        const zählerstand_alt = parseFloat(document.getElementById('zählerstand_alt').value);
        const zählerstand_umpumpen = parseFloat(document.getElementById('zählerstand_umpumpen').value);
        const zählerstand_neu = parseFloat(document.getElementById('zählerstand_neu').value);

        if (Number.isFinite(zählerstand_alt) && Number.isFinite(zählerstand_umpumpen) && Number.isFinite(zählerstand_neu) && zählerstand_alt <= zählerstand_umpumpen && zählerstand_umpumpen <= zählerstand_neu && kundenname !== 'unbekannt') {
            const verbrauch_umpumpen = zählerstand_umpumpen - zählerstand_alt;
            const verbrauch_tanken = zählerstand_neu - zählerstand_umpumpen;
            const kosten_umpumpen = verbrauch_umpumpen * literpreis;
            const kosten_tanken = verbrauch_tanken * literpreis;
            const EAN = produkte.find(produkt => produkt.Bezeichnung === tankSelect.value)?.EAN ?? 'unbekannt';

            form_berechnung.style.display = 'none';

            const Datensatz = `${new Date().toISOString().split('T')[0]};${new Date().toISOString().split('T')[1].split(':').slice(0, 2).join(':')};T;${aktuelleKundenID};${kunden.find(k => k.schlüssel === aktuelleKundenID)?.uid};${EAN};"${selectedTank.Bezeichnung} - ${verbrauch_tanken} l <br>- Zählerstand_alt: #${zählerstand_alt} l<br>- Zählerstand_neu: #${zählerstand_neu} l<br>- Umpumpen: #${verbrauch_umpumpen} l";${selectedTank.Kategorie};${kosten_tanken.toFixed(2)};${selectedTank.MwSt}`;

            const Berechnung = `
            <h1>ClubCash Tankstelle</h1>
            <p>Berechnung</p>
            <table>
                <tr>
                    <td>Schlüsselnummer</td>
                    <td>${aktuelleKundenID}</td>
                </tr>
                <tr>
                    <td>Kundenname</td>
                    <td>${kundenname}</td>
                 </tr>
                 <tr>
                    <td>Produkt</td>
                    <td>${tankSelect.value}</td>
                 </tr>
                 <tr>
                    <td>Literpreis</td>
                    <td>${document.getElementById('Literpreis').textContent} €</td>
                 </tr>
                 <tr>
                    <td>Zählerstand alt</td>
                    <td>${document.getElementById('zählerstand_alt').value}</td>
                </tr>
                <tr>
                    <td>Verbrauch Umpumpen</td>
                    <td>${verbrauch_umpumpen} l</td>
                </tr>
                <tr>
                    <td>Zählerstand nach Tanken</td>
                    <td>${document.getElementById('zählerstand_neu').value}</td>
                </tr>
                <tr>
                    <td>getankt</td>
                    <td class="tankkosten">${verbrauch_tanken} l</td>
                </tr>
                <tr>
                    <td>Kosten</td>
                    <td class="tankkosten">${kosten_tanken.toFixed(2)} €</td> 
                </tr>

            </table>

            <button id="bezahlenButton" type="button" style="background-color: var(--primary-color); color: var(--text-color-dark);" >bezahlen</button>
       
            <button id="abbrechenButton" type="button" style="background-color: var(--error-color); color: var(--text-color-light);" onclick="Abbrechen();">abbrechen</button>
            `;

            ErbenisDiv.innerHTML = Berechnung;

            // Bezahlen durchführen
            const bezahlenButton = document.getElementById('bezahlenButton');
            bezahlenButton.addEventListener('click', function() {
                fetch('save-umsatz-tanken.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        datensatz: Datensatz,
                        produktBezeichnung: selectedTank?.Bezeichnung ?? tankSelect.value,
                        produktEAN: EAN,
                        zaehlerstandNeu: zählerstand_neu
                    })
                })
                .then(async (response) => {
                    const result = await response.json().catch(() => ({ success: false, error: 'Ungültige Serverantwort.' }));
                    if (!response.ok || !result.success) {
                        throw new Error(result.error || 'Fehler beim Speichern in umsatz.csv.');
                    }

                    ErbenisDiv.innerHTML = `
                    <h1>ClubCash Tankstelle</h1>
                    <p class="zentriert" style="margin: 1.5em;">Der Kauf wurde erfolgreich abgeschlossen!</p>
                    <button id="neuButton" type="button" style="background-color: var(--primary-color); color: var(--text-color-dark);" onclick="window.location.reload();">neuer Kauf</button>
                    `;

                })
                .catch((error) => {
                    alert(`Fehler beim Speichern: ${error.message}`);
                });
                
            });

        } else {
            alert('Die Eingaben sind fehlerhaft und können nicht verarbeitet werden - Bitte korrigieren!');
        }

    });

    //Abrechen Funktion
    function Abbrechen() {
        if (kasse) {
            window.location.href = './kasse';
        } else {
            window.location.reload();
        }
    }

</script>
