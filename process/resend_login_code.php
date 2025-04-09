<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";
include_once "../utils/email_helper.php";

initSecureSession();

// Vérifier si un utilisateur est en attente de vérification
if (!isset($_SESSION['pending_user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Générer un nouveau code de vérification
$verificationCode = sprintf("%06d", mt_rand(100000, 999999));
$_SESSION['login_verification_code'] = $verificationCode;
$_SESSION['login_verification_expiry'] = time() + 600; // 10 minutes

// Envoyer l'email avec le code
$emailSent = sendLoginVerificationEmail($_SESSION['pending_user_email'], $verificationCode);

// Rediriger avec message approprié
if ($emailSent) {
    $_SESSION['success_message'] = "Un nouveau code de vérification a été envoyé à votre adresse email.";
} else {
    $_SESSION['errors'] = ["L'envoi du nouveau code a échoué. Veuillez réessayer."];
}

header('Location: ../auth/verify_login.php');
exit;
?>