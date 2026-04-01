<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$redirect = (string)($_GET['redirect'] ?? 'index.php');
if ($redirect === '' || str_contains($redirect, '://') || str_starts_with($redirect, '/')) {
    $redirect = 'index.php';
}

try {
    $config = loadKasseConfig();
    $token = (string)($_COOKIE[authCookieName()] ?? '');
    if (validateAuthToken($token, $config)) {
        header('Location: ' . $redirect);
        exit;
    }
} catch (Throwable $e) {
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubCash Login</title>
    <link rel="stylesheet" href="./style-kasse.css">
    <link rel="stylesheet" href="../farben.css">
</head>
<body>
    <div id="Anmeldungsfenster">
        <img src="../grafik/ClubCashLogo-gelbblauweiss.svg" alt="ClubCash" id="anmelde-logo">
        <h2>Sicherheitscode</h2>
        <input type="password" id="sicherheitscode" placeholder="Code scannen" autocomplete="off">
        <button id="anmeldebutton">Anmelden</button>
        <p id="statusfeld"></p>
    </div>

    <script>
        const statusEl = document.getElementById('statusfeld');
        const codeEl = document.getElementById('sicherheitscode');
        const btnEl = document.getElementById('anmeldebutton');
        const redirectTarget = <?php echo json_encode($redirect, JSON_UNESCAPED_SLASHES); ?>;

        async function login() {
            const code = codeEl.value.trim();
            console.log('Eingegebener Sicherheitscode:', code);

            if (!code) {
                statusEl.textContent = 'Bitte einen Sicherheitscode eingeben.';
                return;
            }

            btnEl.disabled = true;
            statusEl.textContent = 'Pruefung laeuft...';

            try {
                const response = await fetch('sicherheitscode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: new URLSearchParams({ code }).toString()
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    statusEl.textContent = data.message || 'Anmeldung fehlgeschlagen.';
                    btnEl.disabled = false;
                    return;
                }

                statusEl.textContent = 'Anmeldung erfolgreich.';
                window.location.href = redirectTarget;
            } catch (error) {
                statusEl.textContent = 'Serverfehler bei der Anmeldung.';
                btnEl.disabled = false;
            }
        }

        btnEl.addEventListener('click', login);
        codeEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                login();
            }
        });
    </script>
</body>
</html>
