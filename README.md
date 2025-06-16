# ClubCash

**ClubCash** ist ein  
- bargeldloses, webbasiertes **Bezahlsystem**,  
- entwickelt für **Flugsport-Vereine**,  
- zum Bezahlen von z. B. Getränken, Eis, Süßigkeiten und Merchandise-Produkten,  
- ausschließlich für **Vereinsmitglieder**,  
- über **Bezahlterminals**,  
- mit **EAN-Produktstrichcodes** und  
- kostengünstigen, unverschlüsselten **RFID-Chips**,  
- mit direkter Anbindung an [Vereinsflieger.de](https://www.vereinsflieger.de).

## Voraussetzungen

Benötigt wird:  
- ein Gerät mit **Webbrowser** und zumindest temporärer Internetverbindung (z. B. Android-Tablet, Raspberry Pi, Touchscreen-PC – betriebssystemunabhängig),  
- ein **Strichcodescanner**,  
- ggf. ein **RFID-Chipkartenlesegerät** sowie  
- ein **Webserver** oder gemieteter **Webspace**.

> Vorhandene Bezahlsysteme können integriert werden.

---

## 🔍 Demo

[👉 LIVE DEMO Portal](https://demo.clubcash.net/)  
[👉 LIVE DEMO Kassenmodul](https://demo.clubcash.net/kasse)  

**Zugangsdaten für Demo:**  
- Benutzername: `max@mustermann.de`  
- Passwort: `123123123`

> Die Demo wird täglich neu gestartet. Bitte keine Echtdaten verwenden.

- Zur Simulation der Strichcodescanners und des RFID Lesegerätes im Kassenmodul können die Codes (EAN/Chipnummer) über die Tastatur eingegeben werden. Z.B kann zur Simulierung einer Bezahlung die Chipnummer "123123123" (+ Eingabe) eintippen werden.

---

## ✈️ Funktionen

- **Integration mit Vereinsflieger.de**  
  - Rollen- und Mitgliederverwaltung wird vollständig übernommen  
  - Abrechnung erfolgt über Vereinsflieger.de

- **Produktverwaltung**  
  - Verwaltung und Pflege von Produkten und Warenbeständen  
  - Produkteingabe per EAN-Strichcode oder über Katalog

- **Bezahlsystem**  
  - Zahlungen über RFID-Chip oder Mitgliedsstrichcode  
  - Kassen funktionieren auch **offline**  
  - Bestehende Hardware kann weiterverwendet werden

- **Benutzerzugriff**  
  - Mitglieder können Buchungen und Kontostände einsehen – über Terminal oder Webinterface

- **Technische Vorteile**  
  - Plattformunabhängig, webbasiert  
  - Open Source  
  - Einfache Software-Updates  
  - Datenexport-Funktion  
  - Backup-Optionen verfügbar

---

## ⚙️ Grundlegende Funktionsweise

### 1. Kassenmodul
- Scannen eines oder mehrerer EAN-Strichcodes  
- Produktauswahl aus einer Liste möglich  
- Bezahlen durch RFID-Chip oder Mitgliedsstrichcode  
- Zusatzfunktionen:
  - Abfrage von Kontostand & Käufen ohne Produktscan
  - Tagesübersicht über Verkäufe (optional)
  - Preisaktualisierung (z. B. für Mittagessen)

### 2. Portalmodul
- Login mit E-Mail & Schlüsselnummer (Mitglieder)  
- Login mit Vereinsflieger-Zugangsdaten (Administratoren)  
- Persönliche Einstellungen:
  - Eigene Daten & Käufe mit Filteroptionen  
- Verwaltung & Analyse:
  - Mitglieder- & Produktliste  
  - Wareneingänge  
  - Umsatzberichte mit Filterung  
  - Abrechnung mit Vereinsflieger.de  
  - Systemkonfiguration & Datenexport

---

## 🛠️ Systemanforderungen

- Aktives Konto bei [Vereinsflieger.de](https://www.vereinsflieger.de)  
- Einrichtung eines **APPKEYs** für die API-Anbindung  
- Aktuell erlaubt Vereinsflieger.de **500 API-Anfragen pro Tag/APPKEY**  
- Absicherung des Webservers per `.htaccess` empfohlen

---

## 🛒 Kaufempfehlungen

- **Raspberry Pi** mit Touchscreen  
- **Strichcodescanner**  
- **RFID-Lesegerät**  
- **RFID-Karten oder -Chips**  
- **Webspace mit HTTPS-Unterstützung**

---

## 📝 Hinweis

> Dieses Projekt befindet sich in aktiver Entwicklung.  
> Es handelt sich **nicht** um ein Kassensystem im Sinne der **Kassensicherungsverordnung (KassenSichV)**.

---

## 🔧 Installationsanleitung

### 1. Vorbereitung bei Vereinsflieger.de
- APPKEY erzeugen  
- Benutzerdefinierte Felder einrichten:
  - Rollen (z. B. Gast, Mitglied, Verkäufer, Admin)  
  - Chip-IDs / EAN-Kundennummer  
  - Artikelnummern für Produkte

### 2. Webserver einrichten
- Webserver mit HTTPS konfigurieren  
- Installationspaket von GitHub herunterladen *(Link folgt)*  
- Dateien hochladen

### 3. Einrichtung per Webinterface
- Startseite im Browser öffnen  
- APPKEY eingeben  
- Admin-Login durchführen  
- Unter Menü → Einstellungen → Programmeinstellungen:
  - Kassenzugangsdaten & Rollen eintragen  
  - Artikelnummern konfigurieren  
- Danach:
  - Menü → Sicherheitscheck → „Absichern!“ klicken  
  - Menü → Administration → Kundenliste aktualisieren  
  - Datei `install.php` löschen

### 4. Kassenmodul einrichten
- Scanner & RFID-Leser anschließen  
- Browser beim Start automatisch aufrufen:  
  `[Adresse]/kasse` oder  
  `[Adresse]/kasse/index.html?terminal=A` (Mehrere Terminals möglich)

- Benutzername: `kasse`  
- Passwort: wie in Einstellungen festgelegt  
- Tastatur/Maus kann nach Einrichtung entfernt werden

---

## 📄 Lizenz

**GNU Affero General Public License v3.0 (AGPL-3.0)**  
> Jede Person, die den Dienst über ein Netzwerk nutzt, muss Zugang zum vollständigen Quellcode erhalten.

🔗 [Lizenztext auf Deutsch](https://www.gnu.org/licenses/agpl-3.0.de.html)

---

## 🤝 Mitwirken

Beiträge und Rückmeldungen sind willkommen!  
→ [GitHub Issues öffnen](https://github.com/MaScho75/clubcash/issues)  
→ Pull Requests sind gern gesehen.
