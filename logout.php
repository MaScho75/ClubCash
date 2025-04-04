<?php
session_start();
session_destroy(); // Beendet die Sitzung
header('Location: index.php'); // Zurück zum Login
exit();
?>
