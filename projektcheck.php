<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Dieses Skript muss ueber die Kommandozeile ausgefuehrt werden.\n");
    exit(1);
}

const CHECK_OK = 'OK';
const CHECK_WARN = 'WARN';
const CHECK_ERROR = 'ERROR';

$root = __DIR__;
$excludeDirs = ['.git', 'vendor', 'backup'];

$results = [];
$phpFiles = collectFiles($root, ['php'], $excludeDirs);
$jsonFiles = collectFiles($root, ['json'], $excludeDirs);
$csvFiles = collectFiles($root, ['csv'], $excludeDirs);
$textFiles = collectFiles($root, ['php', 'md', 'json', 'csv', 'html', 'css', 'js', 'txt'], $excludeDirs);

addResult($results, CHECK_OK, 'Projekt', 'Starte Projektcheck in ' . $root);

$analysis = analyzePhpFiles($phpFiles, $root, $results);
lintPhpFiles($phpFiles, $results);
validateJsonFiles($jsonFiles, $results);
validateCsvFiles($csvFiles, $results);
checkMojibakePatterns($textFiles, $results);
runProjectSpecificChecks($root, $results);
runAuthSelfTests($root, $results);

$summary = [
    'php_files' => count($phpFiles),
    'php_functions' => count($analysis['functions']),
    'php_methods' => count($analysis['methods']),
    'php_classes' => count($analysis['classes']),
    'include_checks' => $analysis['include_checks'],
    'json_files' => count($jsonFiles),
    'csv_files' => count($csvFiles),
];

printReport($results, $summary);
exit(determineExitCode($results));

function collectFiles(string $root, array $extensions, array $excludeDirs): array
{
    $files = [];
    $directoryIterator = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directoryIterator);

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
            continue;
        }

        $path = $fileInfo->getPathname();
        if (shouldSkipPath($path, $excludeDirs)) {
            continue;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, $extensions, true)) {
            $files[] = $path;
        }
    }

    sort($files);
    return $files;
}

function shouldSkipPath(string $path, array $excludeDirs): bool
{
    $normalized = str_replace('\\', '/', $path);
    foreach ($excludeDirs as $dir) {
        $needle = '/' . trim(str_replace('\\', '/', $dir), '/') . '/';
        if (str_contains($normalized, $needle)) {
            return true;
        }
    }
    return false;
}

function analyzePhpFiles(array $phpFiles, string $root, array &$results): array
{
    $functions = [];
    $methods = [];
    $classes = [];
    $duplicates = [];
    $includeChecks = 0;

    foreach ($phpFiles as $file) {
        $code = file_get_contents($file);
        if ($code === false) {
            addResult($results, CHECK_ERROR, 'Datei lesen', relativePath($file, $root) . ' konnte nicht gelesen werden.');
            continue;
        }

        $analysis = analyzePhpTokens($code);

        foreach ($analysis['classes'] as $class) {
            $classes[] = [
                'file' => $file,
                'name' => $class['name'],
                'line' => $class['line'],
            ];
        }

        foreach ($analysis['functions'] as $function) {
            $key = strtolower($function['name']);
            $record = [
                'file' => $file,
                'name' => $function['name'],
                'line' => $function['line'],
            ];

            if (isset($functions[$key])) {
                $duplicates[$key][] = $record;
            } else {
                $functions[$key] = $record;
            }
        }

        foreach ($analysis['methods'] as $method) {
            $methods[] = [
                'file' => $file,
                'class' => $method['class'],
                'name' => $method['name'],
                'line' => $method['line'],
            ];
        }

        foreach ($analysis['includes'] as $include) {
            $includeChecks++;
            $resolved = resolveIncludeTarget($include['expression'], dirname($file));
            if ($resolved === null) {
                if (str_contains($include['expression'], '$')) {
                    continue;
                }
                addResult(
                    $results,
                    CHECK_WARN,
                    'Include-Pruefung',
                    relativePath($file, $root) . ':' . $include['line'] . ' konnte nicht statisch aufgeloest werden.'
                );
                continue;
            }

            if (!file_exists($resolved)) {
                addResult(
                    $results,
                    CHECK_ERROR,
                    'Include-Pruefung',
                    relativePath($file, $root) . ':' . $include['line'] . ' referenziert fehlende Datei ' . relativePath($resolved, $root)
                );
            }
        }
    }

    foreach ($duplicates as $duplicateName => $entries) {
        $first = $functions[$duplicateName] ?? null;
        if ($first === null) {
            continue;
        }

        $locations = [relativePath($first['file'], $root) . ':' . $first['line']];
        foreach ($entries as $entry) {
            $locations[] = relativePath($entry['file'], $root) . ':' . $entry['line'];
        }

        addResult(
            $results,
            CHECK_ERROR,
            'Doppelte Funktion',
            $first['name'] . ' ist mehrfach definiert: ' . implode(', ', $locations)
        );
    }

    addResult(
        $results,
        CHECK_OK,
        'PHP-Inventur',
        sprintf(
            '%d Funktionen, %d Methoden, %d Klassen, %d Includes analysiert.',
            count($functions),
            count($methods),
            count($classes),
            $includeChecks
        )
    );

    return [
        'functions' => array_values($functions),
        'methods' => $methods,
        'classes' => $classes,
        'include_checks' => $includeChecks,
    ];
}

function analyzePhpTokens(string $code): array
{
    $tokens = token_get_all($code, TOKEN_PARSE);
    $functions = [];
    $methods = [];
    $classes = [];
    $includes = [];

    $braceDepth = 0;
    $pendingClass = null;
    $activeClass = null;
    $classBraceDepth = null;

    $tokenCount = count($tokens);
    for ($index = 0; $index < $tokenCount; $index++) {
        $token = $tokens[$index];

        if (is_string($token)) {
            if ($token === '{') {
                $braceDepth++;
                if ($pendingClass !== null && $activeClass === null) {
                    $activeClass = $pendingClass;
                    $classBraceDepth = $braceDepth;
                    $pendingClass = null;
                }
            } elseif ($token === '}') {
                if ($activeClass !== null && $classBraceDepth === $braceDepth) {
                    $activeClass = null;
                    $classBraceDepth = null;
                }
                $braceDepth = max(0, $braceDepth - 1);
            }
            continue;
        }

        [$id, $text, $line] = $token;

        if (in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            $name = nextTokenText($tokens, $index + 1, [T_STRING]);
            if ($name !== null) {
                $pendingClass = $name;
                $classes[] = [
                    'name' => $name,
                    'line' => $line,
                ];
            }
            continue;
        }

        if ($id === T_FUNCTION) {
            $name = nextTokenText($tokens, $index + 1, [T_STRING]);
            if ($name === null) {
                continue;
            }

            if ($activeClass !== null) {
                $methods[] = [
                    'class' => $activeClass,
                    'name' => $name,
                    'line' => $line,
                ];
            } else {
                $functions[] = [
                    'name' => $name,
                    'line' => $line,
                ];
            }
            continue;
        }

        if (in_array($id, [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], true)) {
            $expressionTokens = [];
            for ($cursor = $index + 1; $cursor < $tokenCount; $cursor++) {
                $candidate = $tokens[$cursor];
                if ($candidate === ';') {
                    break;
                }
                $expressionTokens[] = $candidate;
            }

            $includes[] = [
                'line' => $line,
                'expression' => tokenSequenceToString($expressionTokens),
            ];
        }
    }

    return [
        'functions' => $functions,
        'methods' => $methods,
        'classes' => $classes,
        'includes' => $includes,
    ];
}

function nextTokenText(array $tokens, int $startIndex, array $allowedTokenIds): ?string
{
    $tokenCount = count($tokens);
    for ($index = $startIndex; $index < $tokenCount; $index++) {
        $token = $tokens[$index];

        if (is_string($token)) {
            if ($token === '&') {
                continue;
            }
            return null;
        }

        if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG, T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG], true)) {
            continue;
        }

        if (in_array($token[0], $allowedTokenIds, true)) {
            return $token[1];
        }

        return null;
    }

    return null;
}

function tokenSequenceToString(array $tokens): string
{
    $parts = [];
    foreach ($tokens as $token) {
        $parts[] = is_array($token) ? $token[1] : $token;
    }
    return trim(implode('', $parts));
}

function resolveIncludeTarget(string $expression, string $fileDir): ?string
{
    $tokens = token_get_all('<?php ' . $expression . ';');
    $path = '';
    $started = false;

    foreach ($tokens as $token) {
        if (is_string($token)) {
            if (in_array($token, ['.', '(', ')', ';'], true)) {
                continue;
            }
            return null;
        }

        [$id, $text] = $token;

        if ($id === T_OPEN_TAG) {
            continue;
        }

        if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        if ($id === T_DIR) {
            $path .= $fileDir;
            $started = true;
            continue;
        }

        if ($id === T_CONSTANT_ENCAPSED_STRING) {
            $value = stripcslashes(substr($text, 1, -1));
            $path .= $value;
            $started = true;
            continue;
        }

        return null;
    }

    if (!$started || $path === '') {
        return null;
    }

    if (!preg_match('~^[A-Za-z]:[\\\\/]~', $path) && !str_starts_with($path, DIRECTORY_SEPARATOR)) {
        $path = $fileDir . DIRECTORY_SEPARATOR . $path;
    }

    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $realPath = realpath($normalized);
    return $realPath !== false ? $realPath : $normalized;
}

function lintPhpFiles(array $phpFiles, array &$results): void
{
    foreach ($phpFiles as $file) {
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            addResult($results, CHECK_ERROR, 'PHP-Lint', relativePath($file, __DIR__) . ' -> ' . trim(implode("\n", $output)));
        }
    }

    addResult($results, CHECK_OK, 'PHP-Lint', count($phpFiles) . ' PHP-Dateien geprueft.');
}

function validateJsonFiles(array $jsonFiles, array &$results): void
{
    foreach ($jsonFiles as $file) {
        $raw = file_get_contents($file);
        if ($raw === false) {
            addResult($results, CHECK_ERROR, 'JSON', relativePath($file, __DIR__) . ' konnte nicht gelesen werden.');
            continue;
        }

        json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            addResult(
                $results,
                CHECK_ERROR,
                'JSON',
                relativePath($file, __DIR__) . ' ist ungueltig: ' . json_last_error_msg()
            );
        }
    }

    addResult($results, CHECK_OK, 'JSON', count($jsonFiles) . ' JSON-Dateien geprueft.');
}

function validateCsvFiles(array $csvFiles, array &$results): void
{
    foreach ($csvFiles as $file) {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            addResult($results, CHECK_ERROR, 'CSV', relativePath($file, __DIR__) . ' konnte nicht geoeffnet werden.');
            continue;
        }

        $expectedColumns = null;
        $lineNumber = 0;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNumber++;
            if ($lineNumber === 1 && isset($row[0])) {
                $row[0] = removeUtf8Bom($row[0]);
            }

            if ($row === [null] || $row === []) {
                continue;
            }

            $columnCount = count($row);
            if ($expectedColumns === null) {
                $expectedColumns = $columnCount;
                continue;
            }

            if ($columnCount !== $expectedColumns) {
                addResult(
                    $results,
                    CHECK_ERROR,
                    'CSV',
                    relativePath($file, __DIR__) . ' hat in Zeile ' . $lineNumber . ' ' . $columnCount . ' statt ' . $expectedColumns . ' Spalten.'
                );
                break;
            }
        }

        fclose($handle);
    }

    addResult($results, CHECK_OK, 'CSV', count($csvFiles) . ' CSV-Dateien geprueft.');
}

function removeUtf8Bom(string $value): string
{
    if (str_starts_with($value, "\xEF\xBB\xBF")) {
        return substr($value, 3);
    }
    return $value;
}

function checkMojibakePatterns(array $files, array &$results): void
{
    $pattern = '/Ã.|Â.|�/u';
    $findings = 0;

    foreach ($files as $file) {
        $raw = file_get_contents($file);
        if ($raw === false) {
            continue;
        }

        if (!mb_check_encoding($raw, 'UTF-8')) {
            addResult($results, CHECK_WARN, 'Kodierung', relativePath($file, __DIR__) . ' ist nicht sauber als UTF-8 lesbar.');
            continue;
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        foreach ($lines as $index => $line) {
            if (shouldIgnoreMojibakeMatch($file, $line)) {
                continue;
            }
            if (preg_match($pattern, $line)) {
                $findings++;
                addResult(
                    $results,
                    CHECK_WARN,
                    'Mojibake-Verdacht',
                    relativePath($file, __DIR__) . ':' . ($index + 1) . ' -> ' . shortenLine(trim($line))
                );
            }
        }
    }

    if ($findings === 0) {
        addResult($results, CHECK_OK, 'Kodierung', 'Keine typischen Mojibake-Muster gefunden.');
    }
}

function shouldIgnoreMojibakeMatch(string $file, string $line): bool
{
    $normalizedFile = str_replace('\\', '/', $file);
    if (str_ends_with($normalizedFile, '/projektcheck.php')) {
        return true;
    }

    if (str_ends_with($normalizedFile, '/kasse/auth.php')) {
        if (str_contains($line, 'Mojibake-Variante')) {
            return true;
        }
        if (str_contains($line, "str_contains(\$input, 'Â§')")) {
            return true;
        }
        if (str_contains($line, "str_replace('Â§', '§', \$input)")) {
            return true;
        }
    }

    return false;
}

function shortenLine(string $line, int $maxLength = 140): string
{
    if (mb_strlen($line) <= $maxLength) {
        return $line;
    }
    return mb_substr($line, 0, $maxLength - 3) . '...';
}

function runProjectSpecificChecks(string $root, array &$results): void
{
    $dataCsv = $root . DIRECTORY_SEPARATOR . 'daten' . DIRECTORY_SEPARATOR . 'umsatz.csv';
    $templateCsv = $root . DIRECTORY_SEPARATOR . 'daten_template' . DIRECTORY_SEPARATOR . 'umsatz.csv';

    if (is_file($dataCsv) && is_file($templateCsv)) {
        $dataHeader = readFirstLineUtf8($dataCsv);
        $templateHeader = readFirstLineUtf8($templateCsv);

        if ($dataHeader === $templateHeader) {
            addResult($results, CHECK_OK, 'CSV-Header', 'daten/umsatz.csv und daten_template/umsatz.csv haben denselben Header.');
        } else {
            addResult($results, CHECK_WARN, 'CSV-Header', 'Header von daten/umsatz.csv und daten_template/umsatz.csv unterscheiden sich.');
        }
    }

    $templateConfig = $root . DIRECTORY_SEPARATOR . 'daten_template' . DIRECTORY_SEPARATOR . 'config.json';
    if (is_file($templateConfig)) {
        $config = json_decode((string) file_get_contents($templateConfig), true);
        if (is_array($config) && empty($config)) {
            addResult($results, CHECK_WARN, 'Template-Konfiguration', 'daten_template/config.json ist leer.');
        }
    }
}

function readFirstLineUtf8(string $file): string
{
    $handle = fopen($file, 'rb');
    if ($handle === false) {
        return '';
    }

    $line = fgets($handle);
    fclose($handle);

    if ($line === false) {
        return '';
    }

    return trim(removeUtf8Bom($line));
}

function runAuthSelfTests(string $root, array &$results): void
{
    $authFile = $root . DIRECTORY_SEPARATOR . 'kasse' . DIRECTORY_SEPARATOR . 'auth.php';
    if (!is_file($authFile)) {
        addResult($results, CHECK_WARN, 'Selbsttests', 'kasse/auth.php nicht gefunden, Auth-Selbsttests uebersprungen.');
        return;
    }

    require_once $authFile;

    $assertions = 0;

    $assert = static function (bool $condition, string $message) use (&$results, &$assertions): void {
        $assertions++;
        if (!$condition) {
            addResult($results, CHECK_ERROR, 'Auth-Selbsttest', $message);
        }
    };

    $sampleConfig = [
        'appkey' => 'clubcash-test-app',
        'kassenpw' => 'Geheim123!',
    ];

    $assert(verifyKassenPw('Geheim123!', $sampleConfig), 'Klartext-Passwortpruefung fehlgeschlagen.');
    $assert(!verifyKassenPw('falsch', $sampleConfig), 'Falsches Klartext-Passwort wurde akzeptiert.');

    $apr1Hash = apacheApr1Hash('Geheim123!', 'salt1234');
    $aprConfig = [
        'appkey' => 'clubcash-test-app',
        'kassenpw' => $apr1Hash,
    ];
    $assert(verifyKassenPw('Geheim123!', $aprConfig), 'APR1-Passwortpruefung fehlgeschlagen.');
    $assert(!verifyKassenPw('falsch', $aprConfig), 'Falsches APR1-Passwort wurde akzeptiert.');

    $payload = random_bytes(12) . 'abc';
    $encoded = b64UrlEncode($payload);
    $decoded = b64UrlDecode($encoded);
    $assert($decoded === $payload, 'Base64URL Roundtrip fehlgeschlagen.');

    $token = createAuthToken($sampleConfig, 60);
    $assert(validateAuthToken($token, $sampleConfig), 'Gueltiges Auth-Token wurde verworfen.');

    $expiredToken = createAuthToken($sampleConfig, -1);
    $assert(!validateAuthToken($expiredToken, $sampleConfig), 'Abgelaufenes Token wurde akzeptiert.');

    $originalScriptName = $_SERVER['SCRIPT_NAME'] ?? null;
    $_SERVER['SCRIPT_NAME'] = '/kasse/index.php';
    $assert(authCookiePath() === '/kasse/', 'authCookiePath() liefert fuer /kasse/index.php nicht /kasse/.');
    if ($originalScriptName === null) {
        unset($_SERVER['SCRIPT_NAME']);
    } else {
        $_SERVER['SCRIPT_NAME'] = $originalScriptName;
    }

    $candidates = buildPasswordCandidates('A');
    $assert(in_array('A', $candidates, true), 'buildPasswordCandidates() liefert das Original nicht zurueck.');

    addResult($results, CHECK_OK, 'Auth-Selbsttests', $assertions . ' Auth-Pruefungen ausgefuehrt.');
}

function addResult(array &$results, string $level, string $title, string $message): void
{
    $results[] = [
        'level' => $level,
        'title' => $title,
        'message' => $message,
    ];
}

function printReport(array $results, array $summary): void
{
    echo "ClubCash Projektcheck\n";
    echo str_repeat('=', 72) . "\n";

    foreach ($results as $result) {
        echo str_pad('[' . $result['level'] . ']', 9) . ' ' . $result['title'] . ': ' . $result['message'] . "\n";
    }

    echo str_repeat('-', 72) . "\n";
    echo 'Zusammenfassung: '
        . $summary['php_files'] . ' PHP-Dateien, '
        . $summary['php_functions'] . ' Funktionen, '
        . $summary['php_methods'] . ' Methoden, '
        . $summary['php_classes'] . ' Klassen, '
        . $summary['json_files'] . ' JSON-Dateien, '
        . $summary['csv_files'] . ' CSV-Dateien.'
        . "\n";
}

function determineExitCode(array $results): int
{
    foreach ($results as $result) {
        if ($result['level'] === CHECK_ERROR) {
            return 1;
        }
    }
    return 0;
}

function relativePath(string $path, string $root): string
{
    $normalizedPath = str_replace('\\', '/', $path);
    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
    if (str_starts_with($normalizedPath, $normalizedRoot . '/')) {
        return substr($normalizedPath, strlen($normalizedRoot) + 1);
    }
    return $normalizedPath;
}
