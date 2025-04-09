<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";
include_once "../utils/recaptcha_helper.php";

initSecureSession();

// Rediriger si déjà connecté
if (isAuthenticated()) {
    header('Location: ../index.php');
    exit;
}

// Initialiser le tableau d'erreurs
$errors = [];

// Traiter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../405.php');
    exit;
}

// Vérifier si l'IP est bloquée
$clientIP = $_SERVER['REMOTE_ADDR'];
if (isIpBlocked($clientIP)) {
    $_SESSION['errors'] = ["Votre adresse IP a été temporairement bloquée pour des raisons de sécurité. Veuillez réessayer plus tard."];
    header('Location: ../auth/login.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    // Enregistrer comme tentative suspecte
    recordSuspiciousIpAttempt($clientIP);
    
    $_SESSION['errors'] = ["Erreur de sécurité : formulaire invalide. Veuillez réessayer."];
    header('Location: ../auth/login.php');
    exit;
}

// Vérification du reCAPTCHA
$recaptchaToken = $_POST['recaptcha_token'] ?? '';
if (empty($recaptchaToken)) {
    // Enregistrer comme tentative suspecte
    recordSuspiciousIpAttempt($clientIP);
    
    $_SESSION['errors'] = ["Vérification de sécurité manquante. Veuillez activer JavaScript dans votre navigateur."];
    header('Location: ../auth/../auth/.php');
    exit;
}

if (!verifyRecaptcha($recaptchaToken, 'login')) {
    // Enregistrement de la tentative suspecte
    recordSuspiciousIpAttempt($clientIP);
    error_log("Tentative de connexion suspecte - Échec reCAPTCHA: " . 
              $clientIP . " - Email: " . ($_POST['email'] ?? 'Non fourni'));
    
    $_SESSION['errors'] = ["Vérification de sécurité échouée. Veuillez réessayer."];
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer et nettoyer les données
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Validation basique
if (empty($email) || empty($password)) {
    $_SESSION['errors'] = ["Veuillez saisir votre email et votre mot de passe."];
    header('Location: ../auth/login.php');
    exit;
}

// Vérifier si le compte est verrouillé
if (isAccountLocked($email)) {
    // Enregistrer comme tentative suspecte (quelqu'un qui essaie un compte verrouillé)
    recordSuspiciousIpAttempt($clientIP);
    
    $_SESSION['errors'] = ["Votre compte est temporairement verrouillé en raison de trop nombreuses tentatives de connexion. Veuillez réessayer dans 15 minutes."];
    header('Location: ../auth/login.php');
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, email, password, last_name, first_name, is_admin, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Vérifier si l'email a été vérifié
        if (!$user['email_verified']) {
            $_SESSION['errors'] = ["Vous devez vérifier votre adresse email avant de pouvoir vous connecter. <a href='resend_verification.php'>Renvoyer l'email de vérification</a>."];
            header('Location: ../auth/login.php');
            exit;
        }
        
        // Stocker temporairement les informations utilisateur pour la 2FA
        $_SESSION['pending_user_id'] = $user['id'];
        $_SESSION['pending_user_email'] = $user['email'];
        $_SESSION['pending_user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['pending_user_is_admin'] = (bool)$user['is_admin'];
        
        // Générer un code de vérification et l'envoyer par email
        $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
        $_SESSION['login_verification_code'] = $verificationCode;
        $_SESSION['login_verification_expiry'] = time() + 600; // 10 minutes
        
        // Envoyer l'email avec le code
        include_once "../utils/email_helper.php";
        sendLoginVerificationEmail($user['email'], $verificationCode);
        
        // Réinitialiser les tentatives de connexion et d'IP
        resetLoginAttempts($email);
        resetIpAttempts($clientIP);
        
        // Rediriger vers la page de vérification
        header('Location: ../auth/verify_login.php');
        exit;
    } else {
        // Connexion échouée
        recordFailedLogin($email);
        recordSuspiciousIpAttempt($clientIP);
        
        $_SESSION['errors'] = ["Email ou mot de passe incorrect."];
        header('Location: ../auth/login.php');
        exit;
    }
} catch (PDOException $e) {
    // Log l'erreur pour le débuggage
    error_log("Erreur de connexion: " . $e->getMessage());
    
    $_SESSION['errors'] = ["Erreur de base de données. Veuillez réessayer plus tard."];
    header('Location: ../auth/login.php');
    exit;
}