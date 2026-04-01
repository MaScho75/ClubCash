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

require_once __DIR__ . '/kasse/auth.php';

// Passwort empfangen
if (!isset($_POST['password']) || empty($_POST['password'])) {
    echo json_encode(['error' => 'Kein Passwort übergeben']);
    exit;
}

$password = (string)$_POST['password'];

// Hash erzeugen mit identischer APR1-Logik wie im Kassen-Login
$hash = apacheApr1Hash($password, randomApr1Salt());

// Rückgabe als JSON
echo json_encode(['htpasswd' => $hash]);
exit;

function randomApr1Salt(): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $salt = '';
    for ($i = 0; $i < 8; $i++) {
        $salt .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $salt;
}
