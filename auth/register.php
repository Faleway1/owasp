<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();
$csrfToken = generateCsrfToken();

// Nettoyage des tokens CSRF expirés
cleanupCsrfTokens();

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

include_once "../partials/top.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Inscription</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="../process/register_process.php" id="register-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <!-- Champ caché pour stocker le token reCAPTCHA -->
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="8" required>
                            <small class="text-muted">8 caractères minimum</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required
                                   value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required
                                   value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="zip_code" class="form-label">Code postal</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code"
                                   value="<?= isset($_POST['zip_code']) ? htmlspecialchars($_POST['zip_code']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date"
                                   value="<?= isset($_POST['birth_date']) ? htmlspecialchars($_POST['birth_date']) : '' ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="register-button">S'inscrire</button>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <p>Déjà inscrit? <a href="login.php">Se connecter</a></p>
                        </div>
                    </form>
                    <div id="recaptcha-error" class="alert alert-danger mt-3" style="display: none;">
                        Une erreur est survenue lors de la vérification de sécurité. Veuillez réessayer ou actualiser la page.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const registerButton = document.getElementById('register-button');
    const recaptchaError = document.getElementById('recaptcha-error');
    
    // Désactiver le bouton et masquer l'erreur précédente
    registerButton.disabled = true;
    registerButton.innerHTML = 'Vérification en cours...';
    recaptchaError.style.display = 'none';
    
    // Exécuter reCAPTCHA
    grecaptcha.enterprise.ready(function() {
      grecaptcha.enterprise.execute('6LdDtPkqAAAAAL0juO4A49LHTI_NJ_ibCEiKaYwk', {action: 'register'})
        .then(function(token) {
          document.getElementById('recaptcha_token').value = token;
          document.getElementById('register-form').submit();
        })
        .catch(function(error) {
          console.error('Erreur reCAPTCHA:', error);
          recaptchaError.style.display = 'block';
          registerButton.disabled = false;
          registerButton.innerHTML = 'S\'inscrire';
        });
    });
    
    // Timeout de sécurité en cas de problème avec reCAPTCHA
    setTimeout(function() {
      if (registerButton.disabled) {
        recaptchaError.style.display = 'block';
        registerButton.disabled = false;
        registerButton.innerHTML = 'S\'inscrire';
      }
    }, 10000); // 10 secondes
  });
</script>

<?php include_once "../partials/bottom.php"; ?>