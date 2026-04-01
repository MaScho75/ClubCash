<?php
declare(strict_types=1);

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

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Ungueltige Anfrage']);
    exit;
}

if (!isset($_POST['password']) || trim((string) $_POST['password']) === '') {
    echo json_encode(['error' => 'Kein Passwort uebergeben']);
    exit;
}

$password = (string) $_POST['password'];

try {
    $encodedPassword = encodeCode39Extended($password);
} catch (InvalidArgumentException $exception) {
    echo json_encode(['error' => $exception->getMessage()]);
    exit;
}

$barcodeText = '*' . $encodedPassword . '*';

$html = sprintf(
    '<div id="strichcodeContainer">'
    . '<p>ClubCash Kassenmodul</p>'
    . '<div id="Sicherheitsstrichcode" class="barcode">%s</div>'
    . '<p>Barcode fuer den Sicherheitscode des Kassenmoduls</p>'
    . '</div>',
    htmlspecialchars($barcodeText, ENT_QUOTES, 'UTF-8')
);

echo json_encode([
    'strichcodeHtml' => $html,
    'barcodeText' => $barcodeText,
]);
exit;

function encodeCode39Extended(string $input): string
{
    $map = getCode39ExtendedMap();
    $encoded = '';

    for ($i = 0, $length = strlen($input); $i < $length; $i++) {
        $character = $input[$i];

        if (!isset($map[$character])) {
            throw new InvalidArgumentException(
                'Das Passwort enthaelt Zeichen, die nicht als Code39-Barcode kodiert werden koennen.'
            );
        }

        $encoded .= $map[$character];
    }

    return $encoded;
}

function getCode39ExtendedMap(): array
{
    static $map = null;

    if ($map !== null) {
        return $map;
    }

    $map = [
        ' ' => ' ',
        '!' => '/A',
        '"' => '/B',
        '#' => '/C',
        '$' => '/D',
        '%' => '/E',
        '&' => '/F',
        "'" => '/G',
        '(' => '/H',
        ')' => '/I',
        '*' => '/J',
        '+' => '/K',
        ',' => '/L',
        '-' => '-',
        '.' => '.',
        '/' => '/O',
        '0' => '0',
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5',
        '6' => '6',
        '7' => '7',
        '8' => '8',
        '9' => '9',
        ':' => '/Z',
        ';' => '%F',
        '<' => '%G',
        '=' => '%H',
        '>' => '%I',
        '?' => '%J',
        '@' => '%V',
        '[' => '%K',
        '\\' => '%L',
        ']' => '%M',
        '^' => '%N',
        '_' => '%O',
        '`' => '%W',
        '{' => '%P',
        '|' => '%Q',
        '}' => '%R',
        '~' => '%S',
    ];

    foreach (range('A', 'Z') as $character) {
        $map[$character] = $character;
    }

    foreach (range('a', 'z') as $character) {
        $map[$character] = '+' . strtoupper($character);
    }

    return $map;
}
