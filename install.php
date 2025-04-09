<?php
// Script d'installation de la Mediatek
echo "<h1>Installation de la Mediatek</h1>";
echo "<p>Initialisation de la base de données...</p>";

// Inclusion du script d'initialisation
include_once "utils/db_init.php";

echo "<p>Installation terminée ! <a href='index.php'>Retourner à l'accueil</a></p>";