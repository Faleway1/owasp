<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";
include_once "../utils/email_helper.php";

initSecureSession();
$csrfToken = generateCsrfToken();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Erreur de sécurité : formulaire invalide. Veuillez réessayer.";
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $errors[] = "Veuillez fournir une adresse email valide.";
        } else {
            try {
                $db = getDbConnection();
                
                // Vérifier si l'utilisateur existe et n'est pas déjà vérifié
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND email_verified = 0");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch();
                    
                    // Générer un nouveau token
                    $verificationToken = generateToken();
                    
                    // Mettre à jour le token et sa date d'expiration
                    $updateStmt = $db->prepare("UPDATE users 
                                               SET verification_token = ?, 
                                                   verification_expiry = DATE_ADD(NOW(), INTERVAL 24 HOUR) 
                                               WHERE id = ?");
                    $updateStmt->execute([$verificationToken, $user['id']]);
                    
                    // Envoyer l'email de vérification
                    $emailSent = sendVerificationEmail($email, $verificationToken);
                    
                    if ($emailSent) {
                        $success = true;
                    } else {
                        $errors[] = "L'envoi de l'email de vérification a échoué. Veuillez réessayer plus tard.";
                    }
                } else {
                    // Ne pas divulguer si l'email existe ou est déjà vérifié pour des raisons de sécurité
                    $success = true;
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur de base de données: " . $e->getMessage();
            }
        }
    }
}

include_once "../partials/top.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Renvoyer l'email de vérification</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p>Si l'adresse email est associée à un compte non vérifié, un nouveau lien de vérification a été envoyé. Veuillez vérifier votre boîte de réception.</p>
                            <p><a href="login.php">Retour à la page de connexion</a></p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <p>Entrez votre adresse email pour recevoir un nouveau lien de vérification.</p>
                        
                        <form method="POST" action="resend_verification.php" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Envoyer le lien de vérification</button>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <p><a href="login.php">Retour à la page de connexion</a></p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../partials/bottom.php"; ?>