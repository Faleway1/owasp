<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();
$csrfToken = generateCsrfToken();

// Si l'utilisateur est déjà connecté, le rediriger
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

include_once "../partials/top.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Connexion</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                            <?php unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach($_SESSION['errors'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="../process/login_process.php" id="login-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <!-- Champ caché pour stocker le token reCAPTCHA -->
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="login-button">Se connecter</button>
                        </div>
                        
                        <div class="mt-3 text-center">
                            <p>Pas encore de compte? <a href="register.php">S'inscrire</a></p>
                            <p><a href="forgot_password.php">Mot de passe oublié?</a></p>
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
  document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const loginButton = document.getElementById('login-button');
    const recaptchaError = document.getElementById('recaptcha-error');
    
    // Désactiver le bouton et masquer l'erreur précédente
    loginButton.disabled = true;
    loginButton.innerHTML = 'Vérification en cours...';
    recaptchaError.style.display = 'none';
    
    // Exécuter reCAPTCHA
    grecaptcha.enterprise.ready(function() {
      grecaptcha.enterprise.execute('6LdDtPkqAAAAAL0juO4A49LHTI_NJ_ibCEiKaYwk', {action: 'LOGIN'})
        .then(function(token) {
          document.getElementById('recaptcha_token').value = token;
          document.getElementById('login-form').submit();
        })
        .catch(function(error) {
          console.error('Erreur reCAPTCHA:', error);
          recaptchaError.style.display = 'block';
          loginButton.disabled = false;
          loginButton.innerHTML = 'Se connecter';
        });
    });
    
    // Timeout de sécurité en cas de problème avec reCAPTCHA
    setTimeout(function() {
      if (loginButton.disabled) {
        recaptchaError.style.display = 'block';
        loginButton.disabled = false;
        loginButton.innerHTML = 'Se connecter';
      }
    }, 10000); // 10 secondes
  });
</script>

<?php include_once "../partials/bottom.php"; ?>  