<?php
include_once "../utils/config.php";
include_once "./partials/top.php";
include_once "../utils/auth.php";

// Initialiser la session sécurisée
initSecureSession();

// Vérifier si l'utilisateur est un admin
if (!isAdmin()) {
    // Rediriger vers la page de connexion avec un message
    $_SESSION['error_message'] = "Accès restreint. Veuillez vous connecter avec un compte administrateur.";
    header('Location: ../login.php');
    exit;
}

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../405.php');
    exit;
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    echo "<div class='error-message'>Erreur de sécurité: formulaire invalide. Veuillez réessayer.</div>";
    echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
    include_once "./partials/bottom.php";
    exit;
}

// Vérifier l'ID du livre
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<div class='error-message'>ID de livre invalide.</div>";
    echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
    include_once "./partials/bottom.php";
    exit;
}

$bookId = intval($_POST['id']);

try {
    $db = getDbConnection();
    
    // Récupérer les informations du livre avant suppression pour l'image
    $stmtGet = $db->prepare("SELECT cover_path FROM books WHERE id = ?");
    $stmtGet->execute([$bookId]);
    $book = $stmtGet->fetch();
    
    if (!$book) {
        echo "<div class='error-message'>Livre non trouvé.</div>";
        echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
        include_once "./partials/bottom.php";
        exit;
    }
    
    // Supprimer le livre de la base de données
    $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
    $result = $stmt->execute([$bookId]);
    
    if ($result) {
        // Supprimer l'image de couverture si elle existe
        if ($book['cover_path'] && file_exists('../' . $book['cover_path'])) {
            unlink('../' . $book['cover_path']);
        }
        
        echo "<div class='success-message'>Le livre a été supprimé avec succès.</div>";
    } else {
        echo "<div class='error-message'>Une erreur s'est produite lors de la suppression du livre.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error-message'>Erreur de base de données: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
include_once "./partials/bottom.php";
?>