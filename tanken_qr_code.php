
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

$kundenId = isset($_GET['ID']) ? trim((string)$_GET['ID']) : '';

if ($kundenId === '') {
	http_response_code(400);
	echo '<p>Fehler: Keine Schlüsselnummer übergeben.</p>';
	exit;
}

$kundenName = 'Unbekannt';
$kundenDatei = __DIR__ . DIRECTORY_SEPARATOR . 'daten' . DIRECTORY_SEPARATOR . 'kunden.json';
if (is_file($kundenDatei)) {
	$kundenInhalt = file_get_contents($kundenDatei);
	$kundenDaten = json_decode((string) $kundenInhalt, true);
	if (is_array($kundenDaten)) {
		foreach ($kundenDaten as $kunde) {
			if (!is_array($kunde)) {
				continue;
			}
			if (isset($kunde['schlüssel']) && (string) $kunde['schlüssel'] === $kundenId) {
				$vorname = isset($kunde['firstname']) ? trim((string) $kunde['firstname']) : '';
				$nachname = isset($kunde['lastname']) ? trim((string) $kunde['lastname']) : '';
				$kundenName = trim($vorname . ' ' . $nachname);
				if ($kundenName === '') {
					$kundenName = 'Unbekannt';
				}
				break;
			}
		}
	}
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$tankUrl = $scheme . '://' . $host . ($basePath !== '' ? $basePath : '') . '/tanken.php?kundenid=' . rawurlencode($kundenId);

$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&ecc=M&data=' . rawurlencode($tankUrl);
$printMode = isset($_GET['print']) && $_GET['print'] === '1';

if ($printMode) {
	echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>QR-Code drucken</title>';
	echo '<style>html,body{margin:0;padding:0;height:100%;font-family:Arial,sans-serif;}body{display:flex;align-items:center;justify-content:center;}div{display:flex;flex-direction:column;align-items:center;gap:8px;}img{width:3cm;height:3cm;}</style>';
	echo '</head><body>';
	echo '<div>';
	echo '<p style="margin:0;"><strong>Tankstellenzugriff für:</strong> ' . htmlspecialchars($kundenName, ENT_QUOTES, 'UTF-8') . '</p>';
	echo '<img src="' . htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8') . '" alt="QR-Code für Tankseite" style="width:5cm;height:5cm;">';
	echo '</div>';
	echo '<script>window.onload=function(){window.print();window.onafterprint=function(){window.close();};};</script>';
	echo '</body></html>';
	exit;
}

$printUrl = 'tanken_qr_code.php?ID=' . rawurlencode($kundenId) . '&print=1';

echo '<div style="display:flex; flex-direction:column; align-items:center; gap:12px;">';
echo '<p style="margin:0;"><strong>Tankstellenzugriff für:</strong> ' . htmlspecialchars($kundenName, ENT_QUOTES, 'UTF-8') . '</p>';
echo '<img src="' . htmlspecialchars($qrCodeUrl, ENT_QUOTES, 'UTF-8') . '" alt="QR-Code für Tankseite" style="width:5cm; height:5cm;">';
echo '<p style="margin:0; text-align:center; word-break:break-all;">';
echo '<a href="' . htmlspecialchars($tankUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($tankUrl, ENT_QUOTES, 'UTF-8') . '</a>';
echo '</p>';
echo '<button id="QRprintButton" type="button" style="background-color: var(--primary-color); color: var(--text-color-dark);" onclick="window.open(\'' . htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8') . '\',\'_blank\',\'width=420,height=520\');">drucken</button>'; // es soll nur der QR-Code gedruckt werden, nicht die ganze Seite 
echo '</div>';
