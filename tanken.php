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
$zusatzschluessel = file_exists('daten/keys.json') ? json_decode(file_get_contents('daten/keys.json'), true) : [];
if (!is_array($zusatzschluessel)) {
    $zusatzschluessel = [];
}
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
$config = json_decode(file_get_contents('daten/config.json'), true);
$version = is_array($config) && isset($config['Version']) ? (string) $config['Version'] : '';
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
    <link rel="apple-touch-icon" href="grafik/tanken-app-icon-192.png">
	
	<link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">
	
	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	
</head>

<body>
    <section id="tankenStartbildschirm" class="tanken-startbildschirm">
        <div class="tanken-logozeile">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" alt="ClubCash" class="tanken-clubcash-logo">
            <img src="grafik/tanken-app-icon-192.png" alt="Tankstelle" class="tanken-tank-logo">
        </div>

        <h1>ClubCash Tankstelle</h1>
        <?php if ($version !== ''): ?>
            <p>Version <?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="tanken-buttonzeile">
            <button type="button" id="installButton" class="tanken-install-button" style="background-color: var(--primary-color); color: var(--text-color-dark);">WebApp installieren</button>
            <button type="button" id="saveLinkButton" class="tanken-install-button" style="background-color: var(--primary-color); color: var(--text-color-dark);">Link speichern</button>
            <button type="button" id="startTankButton" class="tanken-start-button" style="background-color: var(--primary-color); color: var(--text-color-dark);">Tankvorgang starten</button>
        </div>
    </section>

    <form id="tanken-form" class="tanken-form" style="display: none;">

        <div class="tanken-logozeile">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" alt="ClubCash" class="tanken-clubcash-logo">
            <img src="grafik/tanken-app-icon-192.png" alt="Tankstelle" class="tanken-tank-logo">
        </div>

        <h1>ClubCash Tankstelle</h1>
        <?php if ($version !== ''): ?>
            <p>Version <?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        
        <div class="tanken-eingabeblock" id="kundenBereich">
            <p><b>Kunde</b></p>
            <input type="text" id="kundenid_input" name="kundenid" placeholder="ChipNr"  required>
            <p id="KundenName">unbekannt</p>
        </div>

        <div id="treibstoffBereich" class="tanken-eingabeblock" style="display: none;">
            <label for="tank">Treibstoffart</label>
            <select id="tank" name="tank" required>
                <option id="sorte" value=""></option>
            </select>
            <p><span id="Literpreis">0.00</span> €/Liter</p>
        </div>

        <div id="tankdatenBereich" class="tanken-eingabeblock" style="display: none;">
            <label for="verkaufsdatum">Verkaufsdatum</label>
            <input type="date" id="verkaufsdatum" name="verkaufsdatum" required>

            <label for="zählerstand_alt">Zählerstand vor Tankvorgang</label>
            <input type="number" id="zählerstand_alt" name="zählerstand_alt" step="0" required>

            <div id="umpumpenFeld" class="tanken-unterblock" style="display: none;">
                <label for="zählerstand_umpumpen">Zählerstand nach Umpumpen</label>
                <input type="number" id="zählerstand_umpumpen" name="zählerstand_umpumpen" step="0">
            </div>

            <label for="zählerstand_neu">Zählerstand nach Tankvorgang</label>
            <input type="number" id="zählerstand_neu" name="zählerstand_neu" step="0" required>
        </div>

        <div id="aktionenBereich" class="tanken-aktionsblock" style="display: none;">
            <button type="button" style="background-color: var(--primary-color); color: var(--text-color-dark);" id="berechnen">berechnen</button>

            <button type="button" style="background-color: var(--error-color); color: var(--text-color-light);" id="abbrechen" onclick="Abbrechen();">abbrechen</button>
        </div>

    </form>
    <div class="tanken-form" id="ergebnis"></div>
    

</body>
</html>

<script>

    // Daten aus PHP in JavaScript übergeben
    const kunden = <?php echo json_encode($kundenListe); ?>;
    const zusatzschluessel = <?php echo json_encode($zusatzschluessel); ?>;
    const produkte = <?php echo json_encode($produkteListe); ?>;
    let selectedTank;
    
    let kundenname = 'unbekannt';
    let deferredInstallPrompt = null;

    function terminalZeitstempel(zeitpunkt = new Date()) {
        const pad = (wert) => String(wert).padStart(2, '0');

        return {
            datum: `${zeitpunkt.getFullYear()}-${pad(zeitpunkt.getMonth() + 1)}-${pad(zeitpunkt.getDate())}`,
            zeit: `${pad(zeitpunkt.getHours())}:${pad(zeitpunkt.getMinutes())}`
        };
    }

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
                alert('Zum Installieren bitte das Teilen-Menü öffnen und "Zum Home-Bildschirm" auswählen.');
            } else if (!window.isSecureContext) {
                alert('Die WebApp kann nur über HTTPS oder localhost installiert werden. Bitte die Tankseite über eine sichere HTTPS-Adresse öffnen.');
            } else {
                alert('Der Browser bietet die Installation aktuell nicht direkt an. Bitte im Browsermenü "App installieren" oder "Zum Startbildschirm hinzufügen" wählen.');
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
            let reloadingAfterUpdate = false;

            navigator.serviceWorker.addEventListener('controllerchange', function() {
                if (reloadingAfterUpdate) {
                    return;
                }

                reloadingAfterUpdate = true;
                window.location.reload();
            });

            navigator.serviceWorker.register('./tanken-sw.js', { updateViaCache: 'none' }).then(function(registration) {
                registration.update().catch(function(error) {
                    console.error('ServiceWorker-Update fuer Tankseite fehlgeschlagen:', error);
                });

                function activateWaitingWorker(worker) {
                    if (!worker) {
                        return;
                    }

                    worker.postMessage({ type: 'SKIP_WAITING' });
                }

                if (registration.waiting) {
                    activateWaitingWorker(registration.waiting);
                }

                registration.addEventListener('updatefound', function() {
                    const newWorker = registration.installing;
                    if (!newWorker) {
                        return;
                    }

                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            activateWaitingWorker(newWorker);
                        }
                    });
                });
            }).catch(function(error) {
                console.error('ServiceWorker fuer Tankseite konnte nicht registriert werden:', error);
            });
        }
    }

    const tankSelect = document.getElementById('tank');
    const treibstoffBereich = document.getElementById('treibstoffBereich');
    const tankdatenBereich = document.getElementById('tankdatenBereich');
    const aktionenBereich = document.getElementById('aktionenBereich');
    const umpumpenFeld = document.getElementById('umpumpenFeld');
    const zaehlerstandAltInput = document.getElementById('zählerstand_alt');
    const zaehlerstandUmpumpenInput = document.getElementById('zählerstand_umpumpen');
    const zaehlerstandNeuInput = document.getElementById('zählerstand_neu');
    const verkaufsdatumInput = document.getElementById('verkaufsdatum');
    const literpreisAnzeige = document.getElementById('Literpreis');
    verkaufsdatumInput.value = terminalZeitstempel().datum;

    function resetTankdaten() {
        selectedTank = null;
        tankSelect.value = '';
        literpreisAnzeige.textContent = '0.00';
        zaehlerstandAltInput.value = '';
        zaehlerstandUmpumpenInput.value = '';
        zaehlerstandNeuInput.value = '';
        aktualisiereUmpumpenFeld(null);
        tankdatenBereich.style.display = 'none';
        aktionenBereich.style.display = 'none';
    }

    function setzeKundenstatus(istGueltig) {
        treibstoffBereich.style.display = istGueltig ? 'flex' : 'none';
        tankSelect.required = istGueltig;

        if (!istGueltig) {
            resetTankdaten();
        }
    }

    function findeKundeNachSchluessel(schluesselnummer) {
        const kunde = kunden.find(k => String(k.schlüssel) === String(schluesselnummer));
        if (kunde) {
            return kunde;
        }

        const zusatz = zusatzschluessel.find(eintrag => String(eintrag.addkey) === String(schluesselnummer));
        if (!zusatz) {
            return null;
        }

        return kunden.find(k => String(k.uid || k.schlüssel) === String(zusatz.uid)) || null;
    }

    function Kundenprüfung(chipId) {
        const kunde = findeKundeNachSchluessel(chipId);
        if (kunde) {
            kundenname = `${kunde.firstname} ${kunde.lastname}`;
            document.getElementById('KundenName').textContent = kundenname;
            setzeKundenstatus(true);
        } else {
            kundenname = 'unbekannt';
            document.getElementById('KundenName').textContent = kundenname;
            setzeKundenstatus(false);
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

    function aktualisiereUmpumpenFeld(produkt) {
        const umpumpenAktiv = produkt?.Umpumpen === 'true';

        umpumpenFeld.style.display = umpumpenAktiv ? 'flex' : 'none';
        zaehlerstandUmpumpenInput.required = umpumpenAktiv;

        if (!umpumpenAktiv) {
            zaehlerstandUmpumpenInput.value = zaehlerstandAltInput.value;
        }
    }

    zaehlerstandAltInput.addEventListener('input', function() {
        if (umpumpenFeld.style.display === 'none') {
            zaehlerstandUmpumpenInput.value = this.value;
        }
    });

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
                literpreisAnzeige.textContent = preis.toFixed(2);
                const zaehlerstand = selectedProdukt.Zählerstand ?? '';
                zaehlerstandAltInput.value = zaehlerstand;
                zaehlerstandUmpumpenInput.value = zaehlerstand;
                zaehlerstandNeuInput.value = zaehlerstand;
                aktualisiereUmpumpenFeld(selectedProdukt);
                tankdatenBereich.style.display = 'flex';
                aktionenBereich.style.display = 'flex';
            } else {
                resetTankdaten();
            }
        } else {
            resetTankdaten();
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
        const verkaufsdatum = verkaufsdatumInput.value;

        if (!/^\d{4}-\d{2}-\d{2}$/.test(verkaufsdatum)) {
            alert('Bitte ein gültiges Verkaufsdatum auswählen.');
            return;
        }

        if (Number.isFinite(zählerstand_alt) && Number.isFinite(zählerstand_umpumpen) && Number.isFinite(zählerstand_neu) && zählerstand_alt <= zählerstand_umpumpen && zählerstand_umpumpen <= zählerstand_neu && kundenname !== 'unbekannt') {
            const verbrauch_umpumpen = zählerstand_umpumpen - zählerstand_alt;
            const verbrauch_tanken = zählerstand_neu - zählerstand_umpumpen;
            const kosten_umpumpen = verbrauch_umpumpen * literpreis;
            const kosten_tanken = verbrauch_tanken * literpreis;
            const EAN = produkte.find(produkt => produkt.Bezeichnung === tankSelect.value)?.EAN ?? 'unbekannt';
            const aktuellerKunde = findeKundeNachSchluessel(aktuelleKundenID);

            if (!aktuellerKunde) {
                alert('Kunde wurde nicht gefunden.');
                return;
            }

            form_berechnung.style.display = 'none';

            const Berechnung = `
            <div class="tanken-logozeile">
                <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" alt="ClubCash" class="tanken-clubcash-logo">
                <img src="grafik/tanken-app-icon-192.png" alt="Tankstelle" class="tanken-tank-logo">
            </div>
            <h1>ClubCash Tankstelle</h1>
            <p>Berechnung</p>

            <div class="tanken-eingabeblock tanken-ergebnisblock">
                <div class="tanken-ergebniszeile">
                    <span>Schlüsselnummer</span>
                    <strong>${aktuelleKundenID}</strong>
                </div>
                <div class="tanken-ergebniszeile">
                    <span>Kundenname</span>
                    <strong>${kundenname}</strong>
                </div>
                <div class="tanken-ergebniszeile">
                    <span>Verkaufsdatum</span>
                    <strong>${verkaufsdatum}</strong>
                </div>
                <div class="tanken-ergebniszeile">
                    <span>Treibstoffart</span>
                    <strong>${tankSelect.value}</strong>
                </div>
                <div class="tanken-ergebniszeile">
                    <span>Literpreis</span>
                    <strong>${document.getElementById('Literpreis').textContent} €</strong>
                </div>
            </div>

            <div class="tanken-eingabeblock tanken-ergebnisblock">
                <div class="tanken-ergebniszeile">
                    <span>Zählerstand vor Tankvorgang</span>
                    <strong>${document.getElementById('zählerstand_alt').value}</strong>
                </div>
                <div class="tanken-ergebniszeile">
                    <span>Verbrauch Umpumpen</span>
                    <strong>${verbrauch_umpumpen} l</strong>
                </div>
                <div class="tanken-ergebniszeile">
                    <span>Zählerstand nach Tankvorgang</span>
                    <strong>${document.getElementById('zählerstand_neu').value}</strong>
                </div>
            </div>

            <div class="tanken-eingabeblock tanken-ergebnisblock">
                <div class="tanken-ergebniszeile tanken-ergebniszeile-betont">
                    <span>Getankt</span>
                    <strong class="tankkosten">${verbrauch_tanken} l</strong>
                </div>
                <div class="tanken-ergebniszeile tanken-ergebniszeile-betont">
                    <span>Kosten</span>
                    <strong class="tankkosten">${kosten_tanken.toFixed(2)} €</strong>
                </div>
            </div>

            <div class="tanken-aktionsblock">
                <button id="zurueckButton" type="button" style="background-color: var(--primary-color); color: var(--text-color-dark);">zurück</button>
                <button id="bezahlenButton" type="button" style="background-color: var(--success-color); color: var(--text-color-light);">bezahlen</button>
                <button id="abbrechenButton" type="button" style="background-color: var(--error-color); color: var(--text-color-light);" onclick="Abbrechen();">abbrechen</button>
            </div>
            `;

            ErbenisDiv.innerHTML = Berechnung;

            // Bezahlen durchführen
            const zurueckButton = document.getElementById('zurueckButton');
            zurueckButton.addEventListener('click', function() {
                ErbenisDiv.innerHTML = '';
                form_berechnung.style.display = 'flex';
            });

            const bezahlenButton = document.getElementById('bezahlenButton');
            bezahlenButton.addEventListener('click', function() {
                const terminalZeit = terminalZeitstempel();
                const Datensatz = `${verkaufsdatum};${terminalZeit.zeit};T;${aktuelleKundenID};${aktuellerKunde?.uid};${EAN};"${selectedTank.Bezeichnung} - ${verbrauch_tanken} l x ${literpreis.toFixed(2)} €<br>- Zählerstand_alt: #${zählerstand_alt} l<br>- Zählerstand_neu: #${zählerstand_neu} l<br>- Umpumpen: #${verbrauch_umpumpen} l";${selectedTank.Kategorie};${kosten_tanken.toFixed(2)};${selectedTank.MwSt}`;

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
                    <div class="tanken-logozeile">
                        <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" alt="ClubCash" class="tanken-clubcash-logo">
                        <img src="grafik/tanken-app-icon-192.png" alt="Tankstelle" class="tanken-tank-logo">
                    </div>
                    <h1>ClubCash Tankstelle</h1>
                    <div class="tanken-eingabeblock tanken-ergebnisblock">
                        <p class="zentriert">Der Kauf wurde erfolgreich abgeschlossen!</p>
                    </div>
                    <div class="tanken-aktionsblock">
                        <button id="neuButton" type="button" style="background-color: var(--primary-color); color: var(--text-color-dark);" onclick="window.location.reload();">nächster Tankvorgang</button>
                    </div>
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
