<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();
$csrfToken = generateCsrfToken();

$errors = [];
$success = false;
$showForm = true;

// Vérifier les paramètres
$email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
$token = $_GET['token'] ?? '';

if (!$email || !$token) {
    $errors[] = "Lien de réinitialisation invalide ou expiré.";
    $showForm = false;
}

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
    // Rediriger vers le script de traitement
    header("Location: ../process/reset_password_process.php");
    exit;
}

include_once "../partials/top.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Réinitialisation du mot de passe</h2>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p>Votre mot de passe a été réinitialisé avec succès!</p>
                            <p>Vous pouvez maintenant <a href="login.php">vous connecter</a> avec votre nouveau mot de passe.</p>
                        </div>
                    <?php elseif ($showForm): ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="../process/reset_password_process.php" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="8" required>
                                <small class="text-muted">8 caractères minimum</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Réinitialiser le mot de passe</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <p>
                            <a href="forgot_password.php">Demander un nouveau lien de réinitialisation</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "../partials/bottom.php"; ?>