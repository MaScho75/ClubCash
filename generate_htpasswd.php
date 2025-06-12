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

header('Content-Type: application/json');

// Passwort empfangen
if (!isset($_POST['password']) || empty($_POST['password'])) {
    echo json_encode(['error' => 'Kein Passwort übergeben']);
    exit;
}

$password = $_POST['password'];

// Hash erzeugen mit Apache-kompatibler MD5-Implementierung
$hash = apache_md5($password);

// Rückgabe als JSON
echo json_encode(['htpasswd' => $hash]);
exit;

// -----------------------------------
// Eigene Apache MD5 (APR1) Funktion
function apache_md5($plainTextPassword, $salt = null) {
    $salt = $salt ?: substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);

    $magic = '$apr1$';
    $salt = substr($salt, 0, 8);

    $len = strlen($plainTextPassword);
    $text = $plainTextPassword . $magic . $salt;

    $bin = pack('H32', md5($plainTextPassword . $salt . $plainTextPassword));
    for ($i = $len; $i > 0; $i -= 16) {
        $text .= substr($bin, 0, min(16, $i));
    }

    for ($i = $len; $i > 0; $i >>= 1) {
        $text .= ($i & 1) ? "\0" : $plainTextPassword[0];
    }

    $bin = md5_bin($text);

    for ($i = 0; $i < 1000; $i++) {
        $new = ($i & 1) ? $plainTextPassword : $bin;
        if ($i % 3) $new .= $salt;
        if ($i % 7) $new .= $plainTextPassword;
        $new .= ($i & 1) ? $bin : $plainTextPassword;
        $bin = md5_bin($new);
    }

    $final = $bin;

    $passwd = '';
    $passwd .= to64((ord($final[0]) << 16) | (ord($final[6]) << 8) | ord($final[12]), 4);
    $passwd .= to64((ord($final[1]) << 16) | (ord($final[7]) << 8) | ord($final[13]), 4);
    $passwd .= to64((ord($final[2]) << 16) | (ord($final[8]) << 8) | ord($final[14]), 4);
    $passwd .= to64((ord($final[3]) << 16) | (ord($final[9]) << 8) | ord($final[15]), 4);
    $passwd .= to64((ord($final[4]) << 16) | (ord($final[10]) << 8) | ord($final[5]), 4);
    $passwd .= to64(ord($final[11]), 2);

    return $magic . $salt . '$' . $passwd;
}

function to64($v, $n) {
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $ret = '';
    while (--$n >= 0) {
        $ret .= $itoa64[$v & 0x3f];
        $v >>= 6;
    }
    return $ret;
}

function md5_bin($str) {
    return pack('H32', md5($str));
}