<?php
declare(strict_types=1);

function kasseConfigPath(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'daten' . DIRECTORY_SEPARATOR . 'config.json';
}

function authTokenTtlSeconds(): int
{
    return 31536000; // 1 Jahr
}

function loadKasseConfig(): array
{
    $path = kasseConfigPath();
    if (!is_file($path)) {
        throw new RuntimeException('Konfiguration nicht gefunden.');
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Konfiguration konnte nicht gelesen werden.');
    }

    $config = json_decode($raw, true);
    if (!is_array($config)) {
        throw new RuntimeException('Konfiguration ist ungueltig.');
    }

    return $config;
}

function authCookieName(): string
{
    return 'kasse_auth_token';
}

function authCookiePath(): string
{
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\', '/', dirname($scriptName));
    if ($dir === '' || $dir === '.' || $dir === DIRECTORY_SEPARATOR) {
        return '/kasse/';
    }

    return rtrim($dir, '/') . '/';
}

function getAuthSecret(array $config): string
{
    $appKey = (string)($config['appkey'] ?? '');
    $kassenPw = (string)($config['kassenpw'] ?? '');
    if ($appKey === '' || $kassenPw === '') {
        throw new RuntimeException('Sicherheitsrelevante Konfiguration fehlt.');
    }

    return hash('sha256', $appKey . '|' . $kassenPw, true);
}

function verifyKassenPw(string $inputCode, array $config): bool
{
    $stored = trim((string)($config['kassenpw'] ?? ''));
    if ($stored === '' || $inputCode === '') {
        return false;
    }

    $candidates = buildPasswordCandidates($inputCode);

    // APR1-Hashes (Apache htpasswd) plattformunabhaengig validieren.
    if (str_starts_with($stored, '$apr1$')) {
        $parts = explode('$', $stored);
        $salt = $parts[2] ?? '';
        if ($salt === '') {
            return false;
        }

        foreach ($candidates as $candidate) {
            if (hash_equals($stored, apacheApr1Hash($candidate, $salt))) {
                return true;
            }
        }
        return false;
    }

    // Andere crypt-Hashes (z.B. bcrypt), falls vom System unterstuetzt.
    if (str_starts_with($stored, '$')) {
        foreach ($candidates as $candidate) {
            $cryptResult = crypt($candidate, $stored);
            if (is_string($cryptResult) && $cryptResult !== '' && $cryptResult !== '*0' && $cryptResult !== '*1') {
                if (hash_equals($stored, $cryptResult)) {
                    return true;
                }
            }
        }
        return false;
    }

    // Fallback: Klartextvergleich (Bestandskompatibilitaet).
    foreach ($candidates as $candidate) {
        if (hash_equals($stored, $candidate)) {
            return true;
        }
    }
    return false;
}

function buildPasswordCandidates(string $input): array
{
    $candidates = [$input];

    // Haeufige Mojibake-Variante: "Â§" statt "§"
    if (str_contains($input, 'Â§')) {
        $candidates[] = str_replace('Â§', '§', $input);
    }

    if (function_exists('iconv')) {
        $cp1252ToUtf8 = iconv('Windows-1252', 'UTF-8//IGNORE', $input);
        if (is_string($cp1252ToUtf8) && $cp1252ToUtf8 !== '') {
            $candidates[] = $cp1252ToUtf8;
        }

        $utf8ToCp1252 = iconv('UTF-8', 'Windows-1252//IGNORE', $input);
        if (is_string($utf8ToCp1252) && $utf8ToCp1252 !== '') {
            $candidates[] = $utf8ToCp1252;
            $roundTrip = iconv('Windows-1252', 'UTF-8//IGNORE', $utf8ToCp1252);
            if (is_string($roundTrip) && $roundTrip !== '') {
                $candidates[] = $roundTrip;
            }
        }
    }

    $unique = [];
    foreach ($candidates as $candidate) {
        if ($candidate === '') {
            continue;
        }
        $unique[$candidate] = true;
    }

    return array_keys($unique);
}

function apacheApr1Hash(string $plainTextPassword, string $salt): string
{
    $salt = substr($salt, 0, 8);
    $magic = '$apr1$';

    $len = strlen($plainTextPassword);
    $text = $plainTextPassword . $magic . $salt;

    $bin = pack('H32', md5($plainTextPassword . $salt . $plainTextPassword));
    for ($i = $len; $i > 0; $i -= 16) {
        $text .= substr($bin, 0, min(16, $i));
    }

    for ($i = $len; $i > 0; $i >>= 1) {
        $text .= ($i & 1) ? "\0" : $plainTextPassword[0];
    }

    $bin = apacheMd5Bin($text);
    for ($i = 0; $i < 1000; $i++) {
        $new = ($i & 1) ? $plainTextPassword : $bin;
        if ($i % 3) {
            $new .= $salt;
        }
        if ($i % 7) {
            $new .= $plainTextPassword;
        }
        $new .= ($i & 1) ? $bin : $plainTextPassword;
        $bin = apacheMd5Bin($new);
    }

    $final = $bin;
    $passwd = '';
    $passwd .= apacheTo64((ord($final[0]) << 16) | (ord($final[6]) << 8) | ord($final[12]), 4);
    $passwd .= apacheTo64((ord($final[1]) << 16) | (ord($final[7]) << 8) | ord($final[13]), 4);
    $passwd .= apacheTo64((ord($final[2]) << 16) | (ord($final[8]) << 8) | ord($final[14]), 4);
    $passwd .= apacheTo64((ord($final[3]) << 16) | (ord($final[9]) << 8) | ord($final[15]), 4);
    $passwd .= apacheTo64((ord($final[4]) << 16) | (ord($final[10]) << 8) | ord($final[5]), 4);
    $passwd .= apacheTo64(ord($final[11]), 2);

    return $magic . $salt . '$' . $passwd;
}

function apacheTo64(int $v, int $n): string
{
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $ret = '';
    while (--$n >= 0) {
        $ret .= $itoa64[$v & 0x3f];
        $v >>= 6;
    }
    return $ret;
}

function apacheMd5Bin(string $str): string
{
    return pack('H32', md5($str));
}

function b64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function b64UrlDecode(string $value): string|false
{
    $padded = $value . str_repeat('=', (4 - (strlen($value) % 4)) % 4);
    return base64_decode(strtr($padded, '-_', '+/'), true);
}

function createAuthToken(array $config, ?int $ttlSeconds = null): string
{
    $ttl = $ttlSeconds ?? authTokenTtlSeconds();
    $payload = [
        'iat' => time(),
        'exp' => time() + $ttl,
        'nonce' => bin2hex(random_bytes(16)),
        'ctx' => 'kasse-login',
    ];

    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($payloadJson === false) {
        throw new RuntimeException('Token konnte nicht erstellt werden.');
    }

    $payloadEncoded = b64UrlEncode($payloadJson);
    $signature = hash_hmac('sha256', $payloadEncoded, getAuthSecret($config), true);

    return $payloadEncoded . '.' . b64UrlEncode($signature);
}

function validateAuthToken(string $token, array $config): bool
{
    if ($token === '' || strpos($token, '.') === false) {
        return false;
    }

    [$payloadEncoded, $sigEncoded] = explode('.', $token, 2);
    $expectedSig = b64UrlEncode(hash_hmac('sha256', $payloadEncoded, getAuthSecret($config), true));
    if (!hash_equals($expectedSig, $sigEncoded)) {
        return false;
    }

    $payloadJson = b64UrlDecode($payloadEncoded);
    if (!is_string($payloadJson) || $payloadJson === '') {
        return false;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return false;
    }

    if (($payload['ctx'] ?? '') !== 'kasse-login') {
        return false;
    }

    $exp = (int)($payload['exp'] ?? 0);
    if ($exp < time()) {
        return false;
    }

    return true;
}

function setAuthCookie(string $token): void
{
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(authCookieName(), $token, [
        'expires' => time() + authTokenTtlSeconds(),
        'path' => authCookiePath(),
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function clearAuthCookie(): void
{
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(authCookieName(), '', [
        'expires' => time() - 3600,
        'path' => authCookiePath(),
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
