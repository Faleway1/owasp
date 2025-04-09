<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();
$csrfToken = generateCsrfToken();

// Récupérer les erreurs de session
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

// Récupérer les messages de succès
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

include_once "../partials/top.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Mot de passe oublié</h2>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <p><?= htmlspecialchars($success_message) ?></p>
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
                        
                        <p>Entrez votre adresse email pour recevoir un lien de réinitialisation de mot de passe.</p>
                        
                        <form method="POST" action="../process/forgot_password_process.php" id="forgot-password-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <!-- Champ caché pour stocker le token reCAPTCHA -->
                            <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="forgot-password-button">Envoyer le lien de réinitialisation</button>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <p><a href="login.php">Retour à la page de connexion</a></p>
                            </div>
                        </form>
                        <div id="recaptcha-error" class="alert alert-danger mt-3" style="display: none;">
                            Une erreur est survenue lors de la vérification de sécurité. Veuillez réessayer ou actualiser la page.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  document.getElementById('forgot-password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitButton = document.getElementById('forgot-password-button');
    const recaptchaError = document.getElementById('recaptcha-error');
    
    // Désactiver le bouton et masquer l'erreur précédente
    submitButton.disabled = true;
    submitButton.innerHTML = 'Vérification en cours...';
    recaptchaError.style.display = 'none';
    
    // Exécuter reCAPTCHA
    grecaptcha.enterprise.ready(function() {
      grecaptcha.enterprise.execute('6LdDtPkqAAAAAL0juO4A49LHTI_NJ_ibCEiKaYwk', {action: 'forgot_password'})
        .then(function(token) {
          document.getElementById('recaptcha_token').value = token;
          document.getElementById('forgot-password-form').submit();
        })
        .catch(function(error) {
          console.error('Erreur reCAPTCHA:', error);
          recaptchaError.style.display = 'block';
          submitButton.disabled = false;
          submitButton.innerHTML = 'Envoyer le lien de réinitialisation';
        });
    });
    
    // Timeout de sécurité en cas de problème avec reCAPTCHA
    setTimeout(function() {
      if (submitButton.disabled) {
        recaptchaError.style.display = 'block';
        submitButton.disabled = false;
        submitButton.innerHTML = 'Envoyer le lien de réinitialisation';
      }
    }, 10000); // 10 secondes
  });
</script>

<?php include_once "../partials/bottom.php"; ?>