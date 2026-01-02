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

// Prüfen, ob der Benutzer eingeloggt ist

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = json_decode(file_get_contents(__DIR__ . '/daten/config.json'), true);

$mail = new PHPMailer(true);

try {
    // Für Fehlersuche aktivieren:
    $mail->SMTPDebug = 2;

    $mail->isSMTP();
    $mail->Host       = $config['SMTPServer'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['SMTPBenutzer'];
    $mail->Password   = $config['SMTPPasswort'];

    $enc = strtolower($config['SMTPVerschluesselung'] ?? 'tls');
    if ($enc === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;   // 465
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 587
    }
    $mail->Port = (int)$config['SMTPPort'];

    $mail->setFrom($config['SMTPAbsenderadresse'], $config['VereinsnameAbk'] ?? 'ClubCash');
    $mail->addReplyTo($config['SMTPAntwortadresse']);

    $mail->addAddress($config['Email']);

    $mail->isHTML(true);
    $mail->Subject = 'Test E-Mail - ClubCash';
    $mail->Body    = '<h2>Test E-Mail</h2><p>SMTP Versand funktioniert.</p>';
    $mail->AltBody = 'SMTP Versand funktioniert.';

    $mail->send();
    echo 'OK: Test-E-Mail wurde gesendet.';
} catch (Exception $e) {
    echo 'FEHLER: ' . htmlspecialchars($mail->ErrorInfo);
}
?>