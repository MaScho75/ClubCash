<?php

session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}


$folderPath = 'backup'; // Der Ordner mit den Backups
$files = scandir($folderPath); // Listet alle Dateien im Ordner auf

// Filtere "." und ".." aus
$files = array_diff($files, array('.', '..'));

echo "<ul>";
foreach ($files as $file) {
    $filePath = $folderPath . '/' . $file;
    if (is_file($filePath)) {
        echo "<li><a href='$filePath' download>$file</a></li>"; // Zeigt Download-Link für jede Datei
    }
}
echo "</ul>";
?>
