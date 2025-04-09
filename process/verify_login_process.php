<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();

// Traiter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../405.php');
    exit;
}

// Vérifier si un utilisateur est en attente de vérification
if (!isset($_SESSION['pending_user_id']) || !isset($_SESSION['login_verification_code'])) {
    header('Location: ../auth/login.php');
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
    header('Location: ../auth/login.php');
    exit;
}

$errors = [];

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $_SESSION['errors'] = ["Erreur de sécurité : formulaire invalide. Veuillez réessayer."];
    header('Location: ../auth/verify_login.php');
    exit;
}

// Récupération et validation du code
$codeInput = $_POST['verification_code'] ?? '';
$codeInput = str_replace(' ', '', $codeInput); // Enlever les espaces éventuels

if (empty($codeInput)) {
    $_SESSION['errors'] = ["Veuillez saisir le code de vérification."];
    header('Location: ../auth/verify_login.php');
    exit;
}

if ($codeInput !== $_SESSION['login_verification_code']) {
    // Journaliser la tentative
    error_log("Tentative de code 2FA incorrect: IP=" . $_SERVER['REMOTE_ADDR'] . ", Email=" . $_SESSION['pending_user_email']);
    
    $_SESSION['errors'] = ["Code de vérification incorrect. Veuillez réessayer."];
    header('Location: ../auth/verify_login.php');
    exit;
}

// Authentification réussie, transférer les données d'attente vers les variables de session réelles
$_SESSION['user_id'] = $_SESSION['pending_user_id'];
$_SESSION['user_email'] = $_SESSION['pending_user_email'];
$_SESSION['user_name'] = $_SESSION['pending_user_name'];
$_SESSION['is_admin'] = $_SESSION['pending_user_is_admin'];
$_SESSION['authenticated'] = true;

// Nettoyer les variables temporaires
unset($_SESSION['pending_user_id']);
unset($_SESSION['pending_user_email']);
unset($_SESSION['pending_user_name']);
unset($_SESSION['pending_user_is_admin']);
unset($_SESSION['login_verification_code']);
unset($_SESSION['login_verification_expiry']);

// Régénérer l'ID de session pour prévenir la fixation de session
session_regenerate_id(true);

// Définir le flag de succès pour l'affichage du message
$_SESSION['2fa_success'] = true;

// Rediriger vers la page de vérification avec succès 
// (qui affichera le message de succès et redirigera vers index.php)
header('Location: ../auth/verify_login.php');
exit;