<?php
header($_SERVER["SERVER_PROTOCOL"] . " 403 - Accès interdit");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accès interdit</title>
    <link rel="stylesheet" href="assets/css/40x.css">
</head>
<body>
    <div>
        <h1>Erreur 403</h1>
        <p>Accès interdit.<br>Vous n'avez pas les autorisations nécessaires pour accéder à cette ressource.<br><a href="index.php" title="Revenir en page d'accueil">Revenir en page d'accueil</a></p>
    </div>
</body>
</html>