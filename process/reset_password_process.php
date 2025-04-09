<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

initSecureSession();

// Traiter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../405.php');
    exit;
}

$errors = [];

// Vérification du token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $_SESSION['errors'] = ["Erreur de sécurité : formulaire invalide. Veuillez réessayer."];
    header('Location: ../auth/reset_password.php');
    exit;
}

// Récupérer les données du formulaire
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validation des entrées
if (!$email || !$token) {
    $_SESSION['errors'] = ["Lien de réinitialisation invalide ou expiré."];
    header('Location: ../auth/reset_password.php');
    exit;
}

// Validation du mot de passe
if (strlen($password) < 8) {
    $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Les mots de passe ne correspondent pas.";
}

// Si erreurs, rediriger vers le formulaire
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: ../auth/reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
    exit;
}

// Si pas d'erreur, mettre à jour le mot de passe
try {
    $db = getDbConnection();
    
    // Vérifier si le token est valide et n'a pas expiré
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$email, $token]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Hacher le nouveau mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe et effacer le token
        $updateStmt = $db->prepare("UPDATE users 
                                  SET password = ?, 
                                      reset_token = NULL, 
                                      reset_expiry = NULL 
                                  WHERE id = ?");
        $result = $updateStmt->execute([$hashedPassword, $user['id']]);
        
        if ($result) {
            // Nettoyer le token CSRF
            try {
                $deleteStmt = $db->prepare("DELETE FROM csrf_tokens WHERE token = ?");
                $deleteStmt->execute([$_POST['csrf_token']]);
            } catch (PDOException $e) {
                // Ignorer les erreurs de nettoyage
            }
            
            $_SESSION['success_message'] = "Votre mot de passe a été réinitialisé avec succès! Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.";
            header('Location: ../auth/login.php');
            exit;
        } else {
            $_SESSION['errors'] = ["Une erreur s'est produite lors de la mise à jour du mot de passe."];
        }
    } else {
        $_SESSION['errors'] = ["Lien de réinitialisation invalide ou expiré."];
    }
} catch (PDOException $e) {
    $_SESSION['errors'] = ["Erreur de base de données: " . $e->getMessage()];
}

// En cas d'erreur, rediriger vers le formulaire
header("Location: ../auth/reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token));
exit;