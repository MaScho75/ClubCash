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
$externe = json_decode(file_get_contents('daten/externe.json'), true);
$kundenListe = array_map(function($kunde) {
    return [
        'uid' => $kunde['uid'],
        'firstname' => $kunde['firstname'],
        'lastname' => $kunde['lastname'],
        'schlüssel' => $kunde['schlüssel']
    ];
}, $kunden);
$kundenListe = array_merge($kundenListe, is_array($externe) ? array_map(function($kunde) {
    return [
        'uid' => $kunde['schlüssel'], // Externe haben keine uid, daher verwenden wir die Schlüsselnummer als uid
        'firstname' => $kunde['firstname'],
        'lastname' => $kunde['lastname'],
        'schlüssel' => $kunde['schlüssel']
    ];
}, $externe) : []);

// frage die Datei "produkte.json" und speiche alle Produkte ohne filter 

$produkte = json_decode(file_get_contents('daten/produkte.json'), true);
// speichere alle dAtensätze in einem Array ist einen positiven Zählerstand haben und nicht "undefined" sind, also nur die Produkte, die tatsächlich getankt werden können. Alle anderen Produkte werden ignoriert.
$produkteListe = is_array($produkte) ? array_values(array_filter($produkte, function($produkt) {
    $zaehlerstand = $produkt['Zählerstand'] ?? null;
    return $zaehlerstand !== null && $zaehlerstand !== "undefined" && $zaehlerstand > 0;
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
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>ClubCash Tankstelle</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="manifest" href="tanken-manifest.json">
    <link rel="apple-touch-icon" href="grafik/tanken-app-icon.svg">
	
	<link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
	
	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	
</head>

<body>
    <section id="tankenStartbildschirm" class="tanken-startbildschirm">
        <div class="tanken-logozeile">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" alt="ClubCash" class="tanken-clubcash-logo">
            <img src="grafik/tanken-app-icon.svg" alt="Tankstelle" class="tanken-tank-logo">
        </div>

        <h1>ClubCash Tankstelle</h1>

        <div class="tanken-buttonzeile">
            <button type="button" id="installButton" class="tanken-install-button" style="background-color: var(--primary-color); color: var(--text-color-dark);">WebApp installieren</button>
            <button type="button" id="saveLinkButton" class="tanken-install-button" style="background-color: var(--primary-color); color: var(--text-color-dark);">Link speichern</button>
            <button type="button" id="startTankButton" class="tanken-start-button" style="background-color: var(--primary-color); color: var(--text-color-dark);">Tankvorgang starten</button>
        </div>
    </section>

    <form id="tanken-form" class="tanken-form" style="display: none;">

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
    let deferredInstallPrompt = null;

    // KundenID ermitteln aus der URL https://host/index.html?zahl=42
    const urlParams = new URLSearchParams(window.location.search);
    // Holt den Wert von "kundenid" aus der URL
    const KundenID = urlParams.get('kundenid');
    const kasse = urlParams.get('kasse');
    const gespeicherteKundenID = localStorage.getItem('clubcash_tanken_kundenid');
    const aktiveKundenID = KundenID || gespeicherteKundenID || '';
    const startbildschirm = document.getElementById('tankenStartbildschirm');
    const installButton = document.getElementById('installButton');
    const saveLinkButton = document.getElementById('saveLinkButton');
    const startTankButton = document.getElementById('startTankButton');
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const isAppleMobile = /iPad|iPhone|iPod/.test(navigator.userAgent);

    console.log('KundenID aus URL:', KundenID);
    console.log('Kasse aus URL:', kasse);

    if (KundenID) {
        localStorage.setItem('clubcash_tanken_kundenid', KundenID);
    }

    window.addEventListener('beforeinstallprompt', function(event) {
        event.preventDefault();
        deferredInstallPrompt = event;
    });

    installButton.addEventListener('click', async function() {
        if (!deferredInstallPrompt) {
            if (isAppleMobile) {
                alert('Zum Installieren bitte das Teilen-Menue oeffnen und "Zum Home-Bildschirm" auswaehlen.');
            } else if (!window.isSecureContext) {
                alert('Die WebApp kann nur ueber HTTPS oder localhost installiert werden. Bitte die Tankseite ueber eine sichere HTTPS-Adresse oeffnen.');
            } else {
                alert('Der Browser bietet die Installation aktuell nicht direkt an. Bitte im Browsermenue "App installieren" oder "Zum Startbildschirm hinzufuegen" waehlen.');
            }
            return;
        }

        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        installButton.style.display = 'none';
    });

    window.addEventListener('appinstalled', function() {
        installButton.style.display = 'none';
    });

    saveLinkButton.addEventListener('click', async function() {
        const tankLink = window.location.href;

        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'ClubCash Tankstelle',
                    text: 'ClubCash Tankstelle',
                    url: tankLink
                });
                return;
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }
            }
        }

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(tankLink);
                alert('Link wurde in die Zwischenablage kopiert.');
                return;
            } catch (error) {
            }
        }

        prompt('Link speichern:', tankLink);
    });

    startTankButton.addEventListener('click', function() {
        startbildschirm.style.display = 'none';
        document.getElementById('tanken-form').style.display = 'flex';
        document.getElementById('kundenid_input').focus();
    });

    if ('serviceWorker' in navigator) {
        const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        const isSecure = window.isSecureContext || isLocalhost;
        if (isSecure) {
            navigator.serviceWorker.register('./tanken-sw.js').catch(function(error) {
                console.error('ServiceWorker fuer Tankseite konnte nicht registriert werden:', error);
            });
        }
    }

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
    if (!KundenID && aktiveKundenID) {
        const kundenInput = document.getElementById('kundenid_input');
        kundenInput.value = aktiveKundenID;
        Kundenprüfung(aktiveKundenID);
    }

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
                const zaehlerstand = selectedProdukt.Zählerstand ?? '';
                document.getElementById('zählerstand_alt').value = zaehlerstand;
                document.getElementById('zählerstand_umpumpen').value = zaehlerstand;
                document.getElementById('zählerstand_neu').value = zaehlerstand;
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

            const Datensatz = `${new Date().toISOString().split('T')[0]};${new Date().toISOString().split('T')[1].split(':').slice(0, 2).join(':')};T;${aktuelleKundenID};${kunden.find(k => k.schlüssel === aktuelleKundenID)?.uid};${EAN};"${selectedTank.Bezeichnung} - ${verbrauch_tanken} l x ${literpreis.toFixed(2)} €<br>- Zählerstand_alt: #${zählerstand_alt} l<br>- Zählerstand_neu: #${zählerstand_neu} l<br>- Umpumpen: #${verbrauch_umpumpen} l";${selectedTank.Kategorie};${kosten_tanken.toFixed(2)};${selectedTank.MwSt}`;

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
