
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

session_start();

// Prüfen ob Benutzer authentifiziert ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Gewünschte Datei aus Query-Parameter
$file = $_GET['file'] ?? '';
$filepath = 'daten/' . basename($file);

// Sicherheitscheck: Nur Dateien im daten-Verzeichnis erlauben
if (!file_exists($filepath) || dirname(realpath($filepath)) !== realpath('daten')) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// MIME-Type ermitteln und senden
$mime = mime_content_type($filepath);
header('Content-Type: ' . $mime);
readfile($filepath);

?>