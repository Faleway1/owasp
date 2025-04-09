<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();

$errors = [];
$success = false;

// Vérifier les paramètres
$email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
$token = $_GET['token'] ?? '';

if (!$email || !$token) {
    $errors[] = "Lien de vérification invalide ou expiré.";
} else {
    try {
        $db = getDbConnection();
        
        // Vérifier si le token est valide et n'a pas expiré
        $stmt = $db->prepare("SELECT id FROM users 
                             WHERE email = ? AND verification_token = ? 
                             AND verification_expiry > NOW() 
                             AND email_verified = 0");
        $stmt->execute([$email, $token]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Marquer l'email comme vérifié
            $updateStmt = $db->prepare("UPDATE users 
                                       SET email_verified = 1, 
                                           verification_token = NULL, 
                                           verification_expiry = NULL 
                                       WHERE id = ?");
            $result = $updateStmt->execute([$user['id']]);
            
            if ($result) {
                $success = true;
            } else {
                $errors[] = "Une erreur s'est produite lors de la vérification de votre email.";
            }
        } else {
            $errors[] = "Lien de vérification invalide ou expiré.";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données: " . $e->getMessage();
    }
}

include_once "../partials/top.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Vérification d'email</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p>Votre adresse email a été vérifiée avec succès!</p>
                            <p>Vous pouvez maintenant <a href="login.php">vous connecter</a> à votre compte.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <p>
                            Si votre lien de vérification a expiré, vous pouvez 
                            <a href="resend_verification.php">demander un nouveau lien</a>.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../partials/bottom.php"; ?>