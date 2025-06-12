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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prüfe, ob der richtige Content-Type gesetzt ist
    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
    if (stripos($contentType, "application/json") === false) {
        echo "Fehler: Falscher Content-Type ($contentType)";
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['csvData'])) {
        file_put_contents("../daten/produkte.csv", $data['csvData']);
        echo "CSV erfolgreich gespeichert!";
    } else {
        echo "Fehler: Keine Daten erhalten.";
    }
} else {
    echo "Ungültige Anfrage.";
}
?>