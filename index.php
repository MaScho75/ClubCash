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

/**
 * Copies a directory and its contents recursively
 */


session_start();

$error_message = '';
$selectedLogin = $_GET['role'] ?? '';
if (!in_array($selectedLogin, ['customer', 'admin'], true)) {
    $selectedLogin = '';
}

// Login-Versuche tracken
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt'] = 0;
}

// prüfe ob der Ordner "daten" existiert
if (!is_dir('daten')) {
    //kopiere den Ordner "daten_template" und nenne ihn "daten"
    if (!copyDirectory('daten_template', 'daten')) {
        die('Fehler beim Kopieren des Template-Verzeichnisses');
    }
}

// Prüfe, ob das Verzeichnis "backup" existiert, wenn nicht, erstelle es
if (!is_dir('backup')) {
    if (!mkdir('backup', 0755, true)) {
        die('Fehler beim Erstellen des Backup-Verzeichnisses');
    }
}

// Prüfe, ob die config.json-Datei einen Wert für "appkey" enthält
// Lade die Konfigurationsdatei
$configFile = 'daten/config.json';
$configData = json_decode(file_get_contents($configFile), true);
if (isset($configData['appkey']) && !empty($configData['appkey'])) {
    // App-Key ist gesetzt, weiter mit dem Login
} else {
    // App-Key ist nicht gesetzt, leite zur Konfiguration weiter
    header('Location: install.php');
    exit();
}


// Basis-URL für Vereinsflieger
$baseUrl = 'https://www.vereinsflieger.de';

// Kunden-Daten laden
$kundenDaten = json_decode(file_get_contents('daten/kunden.json'), true);

// Lade externe Kunendaten
$externeKundenDaten = json_decode(file_get_contents('daten/externe.json'), true);

// Füge externe Kundendaten zu den internen Kundendaten hinzu
if (is_array($externeKundenDaten)) {
    foreach ($externeKundenDaten as $externerKunde) {
        // Überprüfen, ob der externe Kunde bereits in den internen Kundendaten existiert
        $exists = false;
        foreach ($kundenDaten as $internerKunde) {
            if ($internerKunde['email'] === $externerKunde['email'] && $internerKunde['schlüssel'] === $externerKunde['schlüssel']) {
                $exists = true;
                break;
            }
        }
        // Wenn der externe Kunde nicht existiert, füge ihn hinzu
        if (!$exists) {
            $kundenDaten[] = [
                'email' => $externerKunde['email'],
                'schlüssel' => $externerKunde['schlüssel'],
                'uid' => $externerKunde['uid'] ?? null, // Optional
                'cc_seller' => $externerKunde['cc_seller'] ?? false, // Standardwert
                'cc_member' => $externerKunde['cc_member'] ?? false, // Standardwert
                'cc_guest' => $externerKunde['cc_guest'] ?? true, // Standardwert
                'cc_admin' => $externerKunde['cc_admin'] ?? false // Standardwert
            ];
        }
    }
}

// Prüfen, ob Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: portal.php');
    exit();
}

// Wartezeiten berechnen (5 * 2^versuche Sekunden)
$customerWaitTime = 5 * pow(2, $_SESSION['login_attempts']);
$customerRemainingTime = max(0, ($_SESSION['last_attempt'] + $customerWaitTime) - time());
$adminWaitTime = 5 * pow(2, $_SESSION['admin_login_attempts']);
$adminRemainingTime = max(0, ($_SESSION['admin_last_attempt'] + $adminWaitTime) - time());

// Wenn POST-Formular gesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedLogin = $_POST['login_type'] ?? '';

    if ($selectedLogin === 'customer') {
        if (!isset($_SESSION['accessToken']) || !isset($_SESSION['tokenExpiry']) || $_SESSION['tokenExpiry'] < time()) {
            $tokenResponse = @file_get_contents("$baseUrl/interface/rest/auth/accesstoken");

            if ($tokenResponse === false) {
                $error_message = "Fehler beim Abrufen des Access Tokens von Vereinsflieger.";
            } else {
                $tokenData = json_decode($tokenResponse, true);
                if (isset($tokenData['accesstoken'])) {
                    $_SESSION['accessToken'] = $tokenData['accesstoken'];
                    $_SESSION['tokenExpiry'] = time() + 3600;
                } else {
                    $error_message = "Ungültige Antwort vom Token-Server.";
                }
            }
        }

        if ($error_message === '') {
            if ($customerRemainingTime > 0) {
                $error_message = "Bitte warten Sie noch {$customerRemainingTime} Sekunden vor dem nächsten Versuch.";
            } else {
                $KundenName = trim($_POST['kundenname'] ?? '');
                $Schlüsselnummer = trim($_POST['schlüsselnummer'] ?? '');

                if (!empty($KundenName) && !empty($Schlüsselnummer)) {
                    foreach ($kundenDaten as $kunde) {
                        if ($kunde['email'] === $KundenName && $kunde['schlüssel'] === $Schlüsselnummer) {
                            $_SESSION['user_authenticated'] = true;
                            $_SESSION['username'] = $KundenName;
                            $_SESSION['customer_login'] = true;
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['last_attempt'] = 0;
                            header('Location: portal.php');
                            exit();
                        }
                    }

                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt'] = time();
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?role=customer&error=' . urlencode("Ungültige Email oder Schlüsselnummer!"));
                    exit();
                } else {
                    $error_message = "Bitte Email und Schlüsselnummer eingeben.";
                }
            }
        }
    } elseif ($selectedLogin === 'admin') {
        if ($adminRemainingTime > 0) {
            $error_message = "Bitte warten Sie noch {$adminRemainingTime} Sekunden vor dem nächsten Versuch.";
        } else {
            require_once 'VereinsfliegerRestInterface.php';

            $userName = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $authentifizier = trim($_POST['authentifizier'] ?? '');

            if (!empty($userName) && !empty($password)) {
                $api = new VereinsfliegerRestInterface();

                if ($api->SignIn($userName, $password, 0, $configData['appkey'], $authentifizier)) {
                    $_SESSION['accessToken'] = $api->GetAccessToken();
                    $_SESSION['tokenExpiry'] = time() + 3600;
                    $_SESSION['user_authenticated'] = true;
                    $_SESSION['username'] = $userName;
                    $_SESSION['customer_login'] = false;
                    $_SESSION['admin_login_attempts'] = 0;
                    $_SESSION['admin_last_attempt'] = 0;
                    header('Location: portal.php');
                    exit();
                }

                $_SESSION['admin_login_attempts']++;
                $_SESSION['admin_last_attempt'] = time();
                header('Location: ' . $_SERVER['PHP_SELF'] . '?role=admin&error=' . urlencode("Ungültige Zugangsdaten!"));
                exit();
            } else {
                $error_message = "Bitte alle erforderlichen Felder ausfüllen.";
            }
        }
    }
}

if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}



function copyDirectory($source, $destination) {
    if (!is_dir($source)) {
        return false;
    }
    
    if (!is_dir($destination)) {
        if (!mkdir($destination, 0755, true)) {
            return false;
        }
    }
    
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcFile = $source . '/' . $file;
            $destFile = $destination . '/' . $file;
            
            if (is_dir($srcFile)) {
                copyDirectory($srcFile, $destFile);
            } else {
                copy($srcFile, $destFile);
            }
        }
    }
    closedir($dir);
    return true;
}

// Lade config.json
$config = json_decode(file_get_contents('daten/config.json'), true);
if ($config === null) {
    die('Fehler beim Lesen der config.json');
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubCash Login</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="style-portal.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="farben.css?v=<?= time(); ?>">
</head>
<body class="portal">
    <div id="login-container">
        <div id="kopf" class="login-header">
            <a href="https://clubcash.net/"><img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; "></a>

        <p><b><a href="<?php echo $config['Webseite']; ?>" target="_blank" style="text-decoration: none; margin: 0px;">
                <span style="font-size: 24px; color: var(--warning-color););"><?php echo $config['Vereinsname']; ?></span>
            </a></b></p>
        <p><b id="login-title"><?php echo $selectedLogin === 'admin' ? 'Admin-Login' : ($selectedLogin === 'customer' ? 'Kunden-Login' : 'Anmeldung'); ?></b></p>
        </div>

        <?php if (!empty($error_message)): ?>
            <p style="text-align: center; color: var(--error-color);"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <div id="login-choice" class="login-choice-buttons" style="display: <?php echo $selectedLogin === '' ? 'flex' : 'none'; ?>;">
            <button class="button login-choice-member-button" type="button" id="show-customer-login">Mitglieder Login</button>
            <button class="button login-choice-customer-button" type="button" id="show-admin-login">Admin Login</button>
        </div>

        <form method="POST" action="" id="customer-login-form" class="login-section" style="display: <?php echo $selectedLogin === 'customer' ? 'block' : 'none'; ?>;">
            <input type="hidden" name="login_type" value="customer">
            <div class="login-grid">
                <div class="login-label">Email</div>
                <div class="login-input-wrap">
                    <input type="text" name="kundenname" id="kundenname" class="login-input">
                </div>

                <div class="login-label">Key</div>
                <div class="login-input-wrap">
                    <input type="password" name="schlüsselnummer" id="schlüsselnummer" class="login-input">
                </div>
            </div>

            <div class="login-actions">
                <input id="customer-submit-button" class="green button login-submit-button" type="submit" value="Anmelden">
                <button class="button login-back-button" type="button" data-back-to-choice="true">zurück</button>
            </div>
        </form>

        <form method="POST" action="" id="admin-login-form" class="login-section" style="display: <?php echo $selectedLogin === 'admin' ? 'block' : 'none'; ?>;">
            <input type="hidden" name="login_type" value="admin">
            <div class="login-grid">
                <div class="login-label">Email</div>
                <div class="login-input-wrap">
                    <input type="text" name="username" id="username" class="login-input">
                </div>

                <div class="login-label">Passwort</div>
                <div class="login-input-wrap">
                    <input type="password" name="password" id="password" class="login-input">
                </div>

                <div class="login-label">Zwei-Faktor-Authentifizierung</div>
                <div class="login-input-wrap">
                    <input type="text" name="authentifizier" id="authentifizier" placeholder="optional" class="login-input">
                </div>
            </div>

            <div class="login-actions">
                <input id="admin-submit-button" class="green button login-submit-button" type="submit" value="Anmelden">
                <button class="button login-back-button" type="button" data-back-to-choice="true">zurück</button>
            </div>
        </form>

        <?php if ($selectedLogin === 'customer' && $customerRemainingTime > 0): ?>
            <p style="text-align: center; color: var(--warning-color); margin-top: 30px;" id="customer-countdown-container">
                Nächster Versuch in <span id="customer-countdown"><?= $customerRemainingTime ?></span> Sekunden möglich.
            </p>
        <?php endif; ?>

        <?php if ($selectedLogin === 'admin' && $adminRemainingTime > 0): ?>
            <p style="text-align: center; color: var(--warning-color); margin-top: 30px;" id="admin-countdown-container">
                Nächster Versuch in <span id="admin-countdown"><?= $adminRemainingTime ?></span> Sekunden möglich.
            </p>
        <?php endif; ?>

        <script>
            (function() {
                const loginChoice = document.getElementById('login-choice');
                const customerForm = document.getElementById('customer-login-form');
                const adminForm = document.getElementById('admin-login-form');
                const loginTitle = document.getElementById('login-title');

                function showSelection(mode) {
                    const isCustomer = mode === 'customer';
                    const isAdmin = mode === 'admin';

                    loginChoice.style.display = mode ? 'none' : 'flex';
                    customerForm.style.display = isCustomer ? 'block' : 'none';
                    adminForm.style.display = isAdmin ? 'block' : 'none';
                    loginTitle.textContent = isCustomer ? 'Mitglieder-Login' : (isAdmin ? 'Admin-Login' : 'Anmeldung');
                }

                document.getElementById('show-customer-login').addEventListener('click', function() {
                    showSelection('customer');
                });

                document.getElementById('show-admin-login').addEventListener('click', function() {
                    showSelection('admin');
                });

                document.querySelectorAll('[data-back-to-choice="true"]').forEach(button => {
                    button.addEventListener('click', function() {
                        window.location.href = 'index.php';
                    });
                });

                function initCountdown(containerId, countdownId, submitButtonId, activeColor) {
                    const container = document.getElementById(containerId);
                    const countdownElement = document.getElementById(countdownId);
                    const submitButton = document.getElementById(submitButtonId);

                    if (!container || !countdownElement || !submitButton) {
                        return;
                    }

                    let timeLeft = parseInt(countdownElement.textContent, 10) || 0;
                    submitButton.disabled = true;

                    function updateCountdown() {
                        if (timeLeft > 0) {
                            countdownElement.textContent = timeLeft;
                            timeLeft--;
                            setTimeout(updateCountdown, 1000);
                            submitButton.style.backgroundColor = 'var(--border-color)';
                        } else {
                            submitButton.disabled = false;
                            container.style.display = 'none';
                            submitButton.style.backgroundColor = activeColor;
                        }
                    }

                    updateCountdown();
                }

                initCountdown('customer-countdown-container', 'customer-countdown', 'customer-submit-button', 'var(--success-color)');
                initCountdown('admin-countdown-container', 'admin-countdown', 'admin-submit-button', 'var(--success-color)');
                showSelection(<?php echo json_encode($selectedLogin); ?>);
            })();
        </script>
    </div>
</body>
</html>
