<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";
include_once "../utils/email_helper.php";
include_once "../utils/recaptcha_helper.php";

initSecureSession();

// Initialiser le tableau d'erreurs
$errors = [];
$success = false;

// Traiter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../405.php');
    exit;
}

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $_SESSION['errors'] = ["Erreur de sécurité : formulaire invalide. Veuillez réessayer."];
    header('Location: ../auth/register.php');
    exit;
}

// Vérification du reCAPTCHA
$recaptchaToken = $_POST['recaptcha_token'] ?? '';
if (empty($recaptchaToken)) {
    $_SESSION['errors'] = ["Vérification de sécurité manquante. Veuillez activer JavaScript dans votre navigateur."];
    header('Location: ../auth/register.php');
    exit;
}

if (!verifyRecaptcha($recaptchaToken, 'register')) {
    // Enregistrement de la tentative suspecte
    error_log("Tentative d'inscription suspecte - Échec reCAPTCHA: " . 
              $_SERVER['REMOTE_ADDR'] . " - Email: " . ($_POST['email'] ?? 'Non fourni'));
    
    $_SESSION['errors'] = ["Vérification de sécurité échouée. Veuillez réessayer."];
    header('Location: ../auth/register.php');
    exit;
}

// Validation des champs
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    $errors[] = "L'adresse email est invalide.";
}

$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (strlen($password) < 8) {
    $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Les mots de passe ne correspondent pas.";
}

$lastName = trim($_POST['last_name'] ?? '');
if (empty($lastName)) {
    $errors[] = "Le nom est obligatoire.";
}

$firstName = trim($_POST['first_name'] ?? '');
if (empty($firstName)) {
    $errors[] = "Le prénom est obligatoire.";
}

$address = trim($_POST['address'] ?? '');
$zipCode = trim($_POST['zip_code'] ?? '');

// Validation de la date de naissance
$birthDate = null;
if (!empty($_POST['birth_date'])) {
    $birthDate = $_POST['birth_date'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
        $errors[] = "Le format de la date de naissance est invalide.";
    }
}

// Si pas d'erreur, on crée l'utilisateur
if (empty($errors)) {
    try {
        $db = getDbConnection();
        
        // Vérifier si l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        } else {
            // Générer un token de vérification
            $verificationToken = generateToken();
            
            // Hacher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer l'utilisateur avec le token de vérification
            $stmt = $db->prepare("INSERT INTO users (email, password, last_name, first_name, address, zip_code, birth_date, verification_token, verification_expiry) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
            $result = $stmt->execute([$email, $hashedPassword, $lastName, $firstName, $address, $zipCode, $birthDate, $verificationToken]);
            
            if ($result) {
                // Envoyer l'email de vérification
                $emailSent = sendVerificationEmail($email, $verificationToken);
                
                if ($emailSent) {
                    $success = true;
                    // Message de succès avec indication de vérifier l'email
                } else {
                    // L'envoi d'email a échoué, mais l'utilisateur a été créé
                    $success = true;
                    $errors[] = "L'inscription a réussi, mais l'envoi de l'email de vérification a échoué. Contactez l'administrateur.";
                }
            } else {
                $errors[] = "Une erreur s'est produite lors de l'inscription.";
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur d'inscription: " . $e->getMessage());
        $errors[] = "Erreur de base de données. Veuillez réessayer plus tard.";
    }
}

if ($success) {
    $_SESSION['success_message'] = "Inscription réussie! Un email de vérification a été envoyé à votre adresse email. Veuillez cliquer sur le lien dans cet email pour activer votre compte.";
    header('Location: login.php');
} else {
    $_SESSION['errors'] = $errors;
    header('Location: ../auth/register.php');
}
exit;
?>