<?php
include_once "./utils/config.php";
include_once "./utils/auth.php";

initSecureSession();

echo "<h1>Test de validation CSRF</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Détails du token soumis</h2>";
    echo "Token soumis: " . htmlspecialchars($_POST['csrf_token'] ?? 'Non défini');
    
    echo "<h2>Vérification du token</h2>";
    
    // État du token en session
    echo "<p>Token en session: ";
    if (isset($_SESSION['csrf_token'])) {
        echo htmlspecialchars($_SESSION['csrf_token']);
        echo " (correspond au token soumis: " . (($_SESSION['csrf_token'] === ($_POST['csrf_token'] ?? '')) ? 'Oui' : 'Non') . ")";
    } else {
        echo "Non défini";
    }
    echo "</p>";
    
    // Vérification de l'existence du token dans la base de données
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT token, session_id, expires_at FROM csrf_tokens WHERE token = ? AND session_id = ?");
        $stmt->execute([$_POST['csrf_token'] ?? '', session_id()]);
        
        echo "<p>Token trouvé en base de données: ";
        if ($stmt->rowCount() > 0) {
            $token = $stmt->fetch();
            echo "Oui (expire le " . htmlspecialchars($token['expires_at']) . ")";
        } else {
            echo "Non";
        }
        echo "</p>";
    } catch (PDOException $e) {
        echo "<p>Erreur lors de la vérification en base de données: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Résultat de la vérification avec la fonction verifyCsrfToken
    echo "<h3>Résultat de la vérification</h3>";
    echo "<pre>";
    var_dump(verifyCsrfToken($_POST['csrf_token'] ?? ''));
    echo "</pre>";
    
    // Trace de la fonction verifyCsrfToken (version de débogage)
    echo "<h3>Trace détaillée de la vérification</h3>";
    echo "<pre>";
    try {
        $tokenToCheck = $_POST['csrf_token'] ?? '';
        
        echo "1. Vérification des conditions de base:\n";
        echo "   - Token vide? " . (empty($tokenToCheck) ? "Oui" : "Non") . "\n";
        echo "   - Session token vide? " . (empty($_SESSION['csrf_token']) ? "Oui" : "Non") . "\n";
        if (!empty($tokenToCheck) && !empty($_SESSION['csrf_token'])) {
            echo "   - Les tokens correspondent? " . ($tokenToCheck === $_SESSION['csrf_token'] ? "Oui" : "Non") . "\n";
        }
        
        if (!empty($tokenToCheck) && !empty($_SESSION['csrf_token']) && $tokenToCheck === $_SESSION['csrf_token']) {
            echo "\n2. Vérification en base de données:\n";
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT token FROM csrf_tokens WHERE token = ? AND session_id = ? AND expires_at > NOW()");
            $stmt->execute([$tokenToCheck, session_id()]);
            
            echo "   - Token trouvé et valide? " . ($stmt->rowCount() > 0 ? "Oui" : "Non") . "\n";
        }
    } catch (Exception $e) {
        echo "Erreur lors du traçage: " . $e->getMessage() . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>Cette page attend une soumission de formulaire POST.</p>";
    echo "<p><a href='csrf_debug.php'>Retourner à la page de diagnostic</a></p>";
}
?>