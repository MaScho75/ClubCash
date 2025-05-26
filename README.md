# ClubCash

**ClubCash** ist ein 
- bargeldloses webasiertes **Bezahlsystem**
- für **Flugsport-Vereine**
- für das Bezahlen von beispielsweise Getränken, Eis, Süssigkeiten und Merchandise-Produkten
- ausschließlich für **Vereinsmitglieder**
- über **Bezahlterminals**
- mit **Produktstrichcodes** und
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
  - Geplante Datenimport-Funktion
  - Verschiedene Backup-Möglichkeiten

---

## ⚙️ Systemanforderungen

- Der Verein benötigt ein aktives Konto bei [Vereinsflieger.de](https://www.vereinsflieger.de).
- Mitgliederverwaltung und Gebührenabrechnung erfolgen über Vereinsflieger.de.
- Für die Anbindung muss bei Vereinsflieger.de ein **APPKEY** generiert werden.
  - Der Zugriff ist aktuell auf **500 API-Anfragen pro Tag pro APPKEY** begrenzt.
- Die verwendeten Verzeichnisse sollten über `.htaccess` abgesichert werden können.

---

## 🛒 Kaufempfehlungen

**RasperryPi** <br>

mit Touchscreen

n.n.

**Strichcodescanner** <br>
n.n.

**RFID-Leser**

n.n.

**PRID Karten/Chips**

n.n.

**Webspace**

n.n.

---


## 📝 Hinweise

Dieses Projekt befindet sich in aktiver Entwicklung. Die **Import-Funktion** ist aktuell geplant, aber noch nicht umgesetzt.
Es handelt sich um keine offizielles Kassensystem nach der Kassensicherungsverordnung.

---

## 📄 Lizenz

Dieses Projekt steht unter der **GNU Affero General Public License v3.0**.  
Das bedeutet, dass jede Person, die den Dienst über ein Netzwerk nutzt (z. B. Webbrowser), auch Zugang zum vollständigen Quellcode erhalten muss.

Weitere Informationen findest du unter:  
👉 [https://www.gnu.org/licenses/agpl-3.0.de.html](https://www.gnu.org/licenses/agpl-3.0.de.html)

---

## 🤝 Mitwirken

Beiträge, Feedback und Ideen sind willkommen! Bitte eröffne ein [Issue](https://github.com/MaScho75/clubcash/issues) oder erstelle einen Pull Request.
