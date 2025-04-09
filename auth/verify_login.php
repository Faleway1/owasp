<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();
$csrfToken = generateCsrfToken();

// Vérifier si un utilisateur est en attente de vérification
if (!isset($_SESSION['pending_user_id']) || !isset($_SESSION['login_verification_code'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si le délai d'expiration est dépassé
if (time() > $_SESSION['login_verification_expiry']) {
    // Nettoyer les variables de session et rediriger
    unset($_SESSION['pending_user_id']);
    unset($_SESSION['pending_user_email']);
    unset($_SESSION['pending_user_name']);
    unset($_SESSION['pending_user_is_admin']);
    unset($_SESSION['login_verification_code']);
    unset($_SESSION['login_verification_expiry']);
    
    $_SESSION['errors'] = ["Le délai de vérification a expiré. Veuillez vous reconnecter."];
    header('Location: login.php');
    exit;
}

// Récupérer les messages d'erreur stockés en session
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

// Vérifier si le succès est stocké en session (après redirection)
$success = isset($_SESSION['2fa_success']) && $_SESSION['2fa_success'] === true;
unset($_SESSION['2fa_success']);

include_once "../partials/top.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Vérification à deux facteurs</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p>Authentification réussie! Vous allez être redirigé...</p>
                        </div>
                        <script>
                            setTimeout(function() {
                                window.location.href = "../index.php";
                            }, 2000);
                        </script>
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
                        
                        <p>Pour finaliser votre connexion, veuillez saisir le code à 6 chiffres qui a été envoyé à <strong><?= htmlspecialchars($_SESSION['pending_user_email']) ?></strong>.</p>
                        
                        <form method="POST" action="../process/verify_login_process.php" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            
                            <div class="mb-3">
                                <label for="verification_code" class="form-label">Code de vérification</label>
                                <input type="text" class="form-control" id="verification_code" name="verification_code" 
                                       placeholder="123456" maxlength="6" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Vérifier</button>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <p>Vous n'avez pas reçu le code? <a href="resend_login_code.php">Envoyer un nouveau code</a></p>
                                <p><a href="login.php">Annuler et retourner à la connexion</a></p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../partials/bottom.php"; ?>