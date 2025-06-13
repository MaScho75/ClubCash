# ClubCash

**ClubCash** ist ein  
- bargeldloses, webbasiertes **Bezahlsystem**,  
- entwickelt für **Flugsport-Vereine**,  
- zum Bezahlen von beispielsweise Getränken, Eis, Süßigkeiten und Merchandise-Produkten,  
- ausschließlich für **Vereinsmitglieder**,  
- über **Bezahlterminals**,  
- mit **EAN-Produktstrichcodes** und  
- kostengünstigen, unverschlüsselten **RFID-Chips**,  
- mit direkter Anbindung an [Vereinsflieger.de](https://www.vereinsflieger.de).

Benötigt wird:  
- ein Gerät mit **Webbrowser** und zumindest temporärer Internetverbindung (z. B. Android-Tablet, Raspberry Pi, Touchscreen-PC, Mausbedienung – betriebssystemunabhängig),  
- ein **Strichcodescanner**,  
- ggf. ein **RFID-Chipkartenlesegerät** sowie  
- ein **Webserver** oder gemieteter **Webspace**.  

Vorhandene Bezahlsysteme können integriert werden.

---

## ✈️ Funktionen

- **Integration mit Vereinsflieger.de**  
  - Rollen- und Mitgliederverwaltung wird vollständig übernommen.  
  - Abrechnung erfolgt über Vereinsflieger.de.

- **Produktverwaltung**  
  - Verwaltung und Pflege von Produkten und Warenbeständen.  
  - Produkte können per EAN-Strichcode oder aus einem Katalog eingebucht werden.

- **Bezahlsystem**  
  - Zahlungen über eigene Barcodes oder kostengünstige, unverschlüsselte RFID-Chips.  
  - Kassen funktionieren auch **offline**.  
  - Bestehende Hardware kann weiterverwendet werden.

- **Benutzerzugriff**  
  - Mitglieder können ihre Buchungen und Kontostände am Terminal oder über das Webinterface einsehen.

- **Technische Vorteile**  
  - Plattformunabhängig und webbasiert  
  - Open Source  
  - Einfache Software-Updates  
  - Datenexport-Funktion  
  - Verschiedene Backup-Optionen

---

## ⚙️ Systemanforderungen

- Ein aktives Konto bei [Vereinsflieger.de](https://www.vereinsflieger.de) ist erforderlich.  
- Die Mitgliederverwaltung sowie die Abrechnung der Einkäufe erfolgen über Vereinsflieger.de.  
- Zur Anbindung muss ein **APPKEY** bei Vereinsflieger.de generiert werden.  
  - Aktuell sind **500 API-Anfragen pro Tag pro APPKEY** möglich.  
- Die verwendeten Webverzeichnisse sollten über `.htaccess` abgesichert werden können.

---

## 🛒 Kaufempfehlungen

Die folgenden Komponenten werden empfohlen (konkrete Modelle folgen):

- **Raspberry Pi** mit Touchscreen  
- **Strichcodescanner**  
- **RFID-Lesegerät**  
- **RFID-Karten/Chips**  
- **Webspace** (mit HTTPS-Unterstützung)

---

## 📝 Hinweise

Dieses Projekt befindet sich in aktiver Entwicklung.  
Es handelt sich **nicht** um ein offizielles Kassensystem im Sinne der **Kassensicherungsverordnung (KassenSichV)**.

---

## 🛠️ Installationsanleitung

### 1. Vorbereitung bei Vereinsflieger.de  
- Erzeuge oder konfiguriere einen **APPKEY**.  
- Richte benutzerdefinierte Felder ein für:  
  - **Rollen** (z. B. Gast, Mitglied, Verkäufer, Admin)  
  - **Chip-IDs/EAN-Kundennummer**  
  - **Artikelnummern** für Produkte

### 2. Webserver einrichten  
- Webserver (mit HTTPS-Verschlüsselung) einrichten oder mieten.  
- Installationspaket von GitHub herunterladen. *(Link folgt)*  
- Dateien auf den Webserver kopieren.  

### 3. Einrichtung über das Webinterface  
- Rufe die Startseite im Browser auf.
- Gege die **APPKEY** aus vereinsflieger.de ein.
- Wähle **Admin-Login** aus.  
- Melde dich mit deinen Vereinsflieger-Zugangsdaten an.  
- Gehe zu **Menü → Einstellungen → Programmeinstellungen** und trage ein:  
  - Passwort für das Kassenmodul  
  - Benutzerrollen aus Vereinsflieger  
  - Artikelnummer für die Datenbüertragung an Vereinsflieger 

- Führe anschließend aus:  
  - **Menü → Einstellungen → Sicherheitscheck → „Absichern!“**  
  - **Menü → Administration → Kundenliste aktualisieren**  
  - Lösche die Datei **install.php** über **Menü → Einstellungen → Sicherheitscheck**

### 4. Kassenmodul einrichten  
- Schließe Strichcodescanner und RFID-Leser an.  
- Richte das System so ein, dass beim Start automatisch der Browser mit der Adresse  
  `[Zieladresse]/kasse` geöffnet wird.  
- Für mehrere Terminals:  
  `[Zieladresse]/kasse/index.html?terminal=A` (Buchstabe kann frei gewählt werden)  
- Benutzername: `kasse`  
- Passwort: wie zuvor festgelegt
- Tastatur und Maus kann nach der Installation und Einrichtung des automatischen Starts enfernt werden. 

---

## 📄 Lizenz

Dieses Projekt steht unter der **GNU Affero General Public License v3.0 (AGPL-3.0)**.  
Das bedeutet: Jede Person, die den Dienst über ein Netzwerk nutzt (z. B. per Webbrowser), muss Zugang zum vollständigen Quellcode erhalten.

👉 Weitere Infos: [https://www.gnu.org/licenses/agpl-3.0.de.html](https://www.gnu.org/licenses/agpl-3.0.de.html)

---

## 🤝 Mitwirken

Beiträge, Ideen und Rückmeldungen sind herzlich willkommen!  
Bitte eröffne ein [Issue](https://github.com/MaScho75/clubcash/issues) oder sende einen Pull Request.
