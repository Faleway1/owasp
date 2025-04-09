<?php
include_once "./utils/config.php";
include_once "./utils/auth.php";

initSecureSession();

echo "<h1>Diagnostic CSRF</h1>";

// Vérifier si un token CSRF existe dans la session
echo "<h2>Token en session</h2>";
if (isset($_SESSION['csrf_token'])) {
    echo "Token CSRF dans la session: " . htmlspecialchars($_SESSION['csrf_token']);
} else {
    echo "Aucun token CSRF dans la session";
}

// Vérifier les tokens dans la base de données
echo "<h2>Tokens en base de données</h2>";
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT token, session_id, expires_at FROM csrf_tokens WHERE session_id = ?");
    $stmt->execute([session_id()]);
    $tokens = $stmt->fetchAll();
    
    if (count($tokens) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Token</th><th>Session ID</th><th>Expiration</th></tr>";
        foreach ($tokens as $t) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($t['token']) . "</td>";
            echo "<td>" . htmlspecialchars($t['session_id']) . "</td>";
            echo "<td>" . htmlspecialchars($t['expires_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Aucun token trouvé pour cette session";
    }
} catch (PDOException $e) {
    echo "Erreur: " . htmlspecialchars($e->getMessage());
}

// Générer un nouveau token et le montrer
echo "<h2>Nouveau token</h2>";
$newToken = generateCsrfToken();
echo "Nouveau token généré: " . htmlspecialchars($newToken);
echo "<p>Session ID actuelle: " . htmlspecialchars(session_id()) . "</p>";

// Ajouter un formulaire de test
echo "<h2>Formulaire de test</h2>";
echo "<form method='post' action='csrf_test.php'>";
echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($newToken) . "'>";
echo "<input type='submit' value='Tester la validation CSRF'>";
echo "</form>";
?>