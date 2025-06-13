# ClubCash

**ClubCash** ist ein 
- bargeldloses webasiertes **Bezahlsystem**
- für **Flugsport-Vereine**
- für das Bezahlen von beispielsweise Getränken, Eis, Süssigkeiten und Merchandise-Produkten
- ausschließlich für **Vereinsmitglieder**
- über **Bezahlterminals**
- mit **EAN Produktstrichcodes** und
- kostengünstigen unverechlüsselten **RFID Chips**
- mit direktem Anschluss an [Vereinsflieger.de](https://www.vereinsflieger.de).
Bebötigt wird
- ein Gerät mit **Webbrowser** und
- zumindest temporären Internetverbindung (Android Tablet,  RaspberryPi, Touchscreen oder Mausbedienung, PC, Betriebssystem unabhängig),
- **Strichcodescanner** und
- ggf. **RFID-Chipkartenlesegerät** und ein
- **Webserver** oder gemieteten **Webspace**.  
Vorhandene Bezahlsysteme können integriert werden.

---

## ✈️ Funktionen

- Integration mit **Vereinsflieger.de**:
  - Rollen- und Mitgliederverwaltung wird vollständig übernommen.
  - Abrechnung über Vereinsflieger.de
- **Produktverwaltung**:
  - Verwaltung und Pflege von Produkten und Warenbeständen.
  - Produkte werden über EAN-Strichcodes oder aus einem Katalog eingebucht.
- **Bezahlsystem**:
  - Zahlung über eigene Barcodes oder günstige, unverschlüsselte RFID-Chips.
  - Kassen funktionieren auch **offline**.
  - Bestehende Hardware anderer Systeme kann weitergenutzt werden.
- **Benutzerzugriff**:
  - Mitglieder können ihre Buchungen und Kontostände am Terminal oder über ein Webinterface einsehen.
- **Technische Vorteile**:
  - Webbasiert & plattformunabhängig
  - Open Source
  - Einfache Software-Updates
  - Datenexport-Funktion
  - verschiedene Backup-Möglichkeiten

---

## ⚙️ Systemanforderungen

- Der Verein benötigt ein aktives Konto bei [Vereinsflieger.de](https://www.vereinsflieger.de).
- Mitgliederverwaltung und Einkaufsabrechnung erfolgen über Vereinsflieger.de.
- Für die Anbindung muss bei Vereinsflieger.de ein **APPKEY** generiert werden.
  - Der Zugriff ist aktuell auf **500 API-Anfragen pro Tag pro APPKEY** begrenzt.
- Die verwendeten Verzeichnisse sollten über `.htaccess` abgesichert werden können.

---

## 🛒 Kaufempfehlungen

**RasperryPi**
mit Touchscreen
n.n.

**Strichcodescanner**
n.n.

**RFID-Leser**
n.n.

**PRID Karten/Chips**
n.n.

**Webspace**
n.n.

---

## 📝 Hinweise

Dieses Projekt befindet sich in aktiver Entwicklung. 
Es handelt sich um keine offizielles Kassensystem nach der Kassensicherungsverordnung.

---

## 🛠️ Installationanleitung

-	Vorbereitung Vereinsflieger.de
    - Generierung/Einrichtung einer APPKEY, soweit noch nicht vorhanden.
    - Einrichtung/Festlegen von benutzerdefinierten oder vorgegebenen Benutzerfeldern mit folgenden Informationen, wo alte Systeme oder vorhandene Systeme übernommen werden können.
      - Rollen (Gast, Mitglied, Verkäufer, Admin)
      - Schlüssel/Key/Chip
    - Anlegen und festlegen einer Artikelnummer  
-	Einrichten oder anmieten eines aus dem Internet erreichbaren Webservers vorzugsweise mit https-Verschlüsselung. 
-	Herunterladen der Installationsdateien aus GitHub (Adresse) 
-	Kopieren der Dateien auf dem Webserver.
-	Aufruf der Internetseite
-	Auswahl zum Admin-Login
-	Anmeldung mit den persönlichen Anmeldedaten aus Vereinsflieger.de
-	Menü - Einstellungen/Programmeinstellungen
-	Eingabe der Verbindungs- und Kontaktdaten
    - APPKEY (s.o.)
    - Passwort für das Kassenmodul
    - Eingabe der zuvor in Vereinsflieger festgelegten Rollen aus den Benutzerfeldern (Gast, Mitglied, Verkäufer, Admin)
    - Artikelnummer
-	Menü - Einstellungen/Sicherheitscheck -> „Absichern!“
-	Menü - Administration/Kundenliste aktualisieren
-	Menü - Einstellungen/Sicherheitscheck -> löschen der **install.php** -Datei
-	Einrichtung des Kassenmoduls für das Bezahlsystem
    - Anschluss Strichcode- und Chipscanner
    - Es wird empfohlen, dass das System so eingerichtet wird, dass beim Einschalten automatisch der Chromebrowser mit der folgenden Adresse gestartet wird.
    - [Zieladresse]/kasse
    - Sollten mehrere Kassen betrieben werden, kann zusätzlich für jede Kasse ein Namen vergeben werden, der sowohl im Kassenmodul als auch in der Abrechnung erscheint. Dazu ist hinter der Adresse „?terminal=A“ einzugeben. Die Buchstabe A kann beliebig ausgetauscht werden. 
[Zieladresse]/kasse/index.html?terminal=A
    - Benutzername: „kasse“
    - Passwort wie bei den Einstellungen eingegeben.

---

## 📄 Lizenz

Dieses Projekt steht unter der **GNU Affero General Public License v3.0**.  
Das bedeutet, dass jede Person, die den Dienst über ein Netzwerk nutzt (z. B. Webbrowser), auch Zugang zum vollständigen Quellcode erhalten muss.

Weitere Informationen findest du unter:  
👉 [https://www.gnu.org/licenses/agpl-3.0.de.html](https://www.gnu.org/licenses/agpl-3.0.de.html)

---

## 🤝 Mitwirken

Beiträge, Feedback und Ideen sind willkommen! Bitte eröffne ein [Issue](https://github.com/MaScho75/clubcash/issues) oder erstelle einen Pull Request.
