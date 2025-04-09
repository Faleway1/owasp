<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";
include_once "../utils/email_helper.php";
include_once "../utils/recaptcha_helper.php";

initSecureSession();

// Initialiser le tableau d'erreurs
$errors = [];

// Traiter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: 405.php');
    exit;
}

// Vérifier si l'IP est bloquée
$clientIP = $_SERVER['REMOTE_ADDR'];
if (isIpBlocked($clientIP)) {
    $_SESSION['errors'] = ["Votre adresse IP a été temporairement bloquée pour des raisons de sécurité. Veuillez réessayer plus tard."];
    header('Location: forgot_password.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    // Enregistrer comme tentative suspecte
    recordSuspiciousIpAttempt($clientIP);
    
    $_SESSION['errors'] = ["Erreur de sécurité : formulaire invalide. Veuillez réessayer."];
    header('Location: forgot_password.php');
    exit;
}

// Vérification du reCAPTCHA
$recaptchaToken = $_POST['recaptcha_token'] ?? '';
if (empty($recaptchaToken)) {
    // Enregistrer comme tentative suspecte
    recordSuspiciousIpAttempt($clientIP);
    
    $_SESSION['errors'] = ["Vérification de sécurité manquante. Veuillez activer JavaScript dans votre navigateur."];
    header('Location: ../auth/forgot_password.php');
    exit;
}

if (!verifyRecaptcha($recaptchaToken, 'forgot_password')) {
    // Enregistrement de la tentative suspecte
    recordSuspiciousIpAttempt($clientIP);
    error_log("Tentative de réinitialisation suspecte - Échec reCAPTCHA: " . 
              $clientIP . " - Email: " . ($_POST['email'] ?? 'Non fourni'));
    
    $_SESSION['errors'] = ["Vérification de sécurité échouée. Veuillez réessayer."];
    header('Location: ../auth/forgot_password.php');
    exit;
}

// Valider l'email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
    $_SESSION['errors'] = ["Veuillez fournir une adresse email valide."];
    header('Location: ../auth/forgot_password.php');
    exit;
}

try {
    $db = getDbConnection();
    
    // Vérifier si l'utilisateur existe
    $stmt = $db->prepare("SELECT id, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Vérifier si l'email est vérifié
        if (!$user['email_verified']) {
            $_SESSION['errors'] = ["Cet email n'a pas encore été vérifié. Veuillez d'abord vérifier votre email ou <a href='resend_verification.php'>demander un nouveau lien de vérification</a>."];
            header('Location: ../auth/forgot_password.php');
            exit;
        }
        
        // Générer un nouveau token
        $resetToken = generateToken();
        
        // Mettre à jour le token et sa date d'expiration (1 heure)
        $updateStmt = $db->prepare("UPDATE users 
                                SET reset_token = ?, 
                                    reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                                WHERE id = ?");
        $updateStmt->execute([$resetToken, $user['id']]);
        
        // Envoyer l'email de réinitialisation
        $emailSent = sendPasswordResetEmail($email, $resetToken);
        
        if (!$emailSent) {
            // Si l'envoi échoue, on enregistre l'erreur mais on ne la montre pas à l'utilisateur
            error_log("Échec d'envoi d'email de réinitialisation à $email");
        }
        
        // Pour des raisons de sécurité, on montre toujours un message de succès
        // même si l'envoi a échoué ou si l'email n'existe pas
    }
    
    // Ne pas révéler si l'email existe ou non pour des raisons de sécurité
    $_SESSION['success_message'] = "Si l'adresse email est associée à un compte, un lien de réinitialisation de mot de passe a été envoyé. Veuillez vérifier votre boîte de réception.";
    header('Location: ../auth/forgot_password.php');
    exit;
    
} catch (PDOException $e) {
    error_log("Erreur de réinitialisation de mot de passe: " . $e->getMessage());
    $_SESSION['errors'] = ["Erreur de base de données. Veuillez réessayer plus tard."];
    header('Location: ../auth/forgot_password.php');
    exit;
}