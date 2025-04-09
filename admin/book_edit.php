<?php
include_once "../utils/regex.php";
include_once "../utils/config.php";
include_once "../utils/upload_helper.php";
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

$errors = [];

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

// Validation des données
if (isset($_POST['title']) && trim($_POST['title']) !== '') {
    $title = trim($_POST['title']);
    $titleLen = strlen($title);
    if ($titleLen < 2 || $titleLen > 150) {
        $errors[] = "Le champ 'Titre' doit contenir entre 2 et 150 caractères.";
    }
} else {
    $errors[] = "Le champ 'Titre' est obligatoire.";
}

if (isset($_POST['isbn']) && trim($_POST['isbn']) !== '') {
    $isbn = trim($_POST['isbn']);
    if (!preg_match($validPatterns['isbn'], $isbn)) {
        $errors[] = "Le champ 'ISBN' doit contenir exactement 13 chiffres.";
    }
} else {
    $errors[] = "Le champ 'ISBN' est obligatoire.";
}

if (isset($_POST['summary']) && trim($_POST['summary']) !== '') {
    $summary = trim($_POST['summary']);
    $summaryLen = strlen($summary);
    if ($summaryLen > 65535) {
        $errors[] = "Le champ 'Résumé' doit contenir au plus 65535 caractères.";
    }
} else {
    $summary = NULL;
}

if (isset($_POST['publication_year']) && trim($_POST['publication_year']) !== '') {
    $publicationYear = trim($_POST['publication_year']);
    if (!preg_match($validPatterns['year'], $publicationYear)) {
        $errors[] = "Le champ 'Année de publication' doit être au format YYYY (ex. : 1997).";
    }
} else {
    $errors[] = "Le champ 'Année de publication' est obligatoire.";
}

// Si des erreurs sont détectées, les afficher
if (count($errors) > 0) {
    echo "<div class='error-message'><ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
    echo "<p><a href='book_edit_form.php?id=" . $bookId . "'>Retour au formulaire d'édition</a></p>";
    include_once "./partials/bottom.php";
    exit;
}

try {
    $db = getDbConnection();
    
    // Récupérer les informations actuelles du livre
    $stmtCurrent = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmtCurrent->execute([$bookId]);
    $currentBook = $stmtCurrent->fetch();
    
    if (!$currentBook) {
        echo "<div class='error-message'>Livre non trouvé.</div>";
        echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
        include_once "./partials/bottom.php";
        exit;
    }
    
    // Vérifier si l'ISBN existe déjà pour un autre livre
    if ($isbn != $currentBook['isbn']) {
        $stmtCheck = $db->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
        $stmtCheck->execute([$isbn, $bookId]);
        if ($stmtCheck->rowCount() > 0) {
            echo "<div class='error-message'>Un autre livre avec cet ISBN existe déjà.</div>";
            echo "<p><a href='book_edit_form.php?id=" . $bookId . "'>Retour au formulaire d'édition</a></p>";
            include_once "./partials/bottom.php";
            exit;
        }
    }
    
    // Gestion de l'image de couverture
    $coverPath = $currentBook['cover_path'];
    
    // Si l'utilisateur a demandé à supprimer l'image
    if (isset($_POST['remove_cover']) && $_POST['remove_cover'] == 'on' && $coverPath) {
        // Supprimer l'ancienne image du système de fichiers
        if (file_exists('../' . $coverPath)) {
            unlink('../' . $coverPath);
        }
        $coverPath = null;
    }
    
    // Si une nouvelle image est téléchargée
    if (!empty($_FILES['cover']['name'])) {
        $uploadResult = processImageUpload($_FILES['cover']);
        if ($uploadResult['success']) {
            // Si une ancienne image existait, la supprimer
            if ($coverPath && file_exists('../' . $coverPath)) {
                unlink('../' . $coverPath);
            }
            $coverPath = $uploadResult['filepath'];
        } else {
            echo "<div class='error-message'>Erreur lors de l'upload de l'image: " . htmlspecialchars($uploadResult['message']) . "</div>";
            echo "<p><a href='book_edit_form.php?id=" . $bookId . "'>Retour au formulaire d'édition</a></p>";
            include_once "./partials/bottom.php";
            exit;
        }
    }
    
    // Mise à jour des données du livre
    $stmt = $db->prepare("UPDATE books SET title = ?, isbn = ?, summary = ?, publication_year = ?, cover_path = ? WHERE id = ?");
    $result = $stmt->execute([$title, $isbn, $summary, $publicationYear, $coverPath, $bookId]);
    
    if ($result) {
        echo "<div class='success-message'>Le livre a été mis à jour avec succès!</div>";
        echo "<p><a href='book_show.php?id=" . $bookId . "'>Voir les détails du livre</a></p>";
        echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
    } else {
        echo "<div class='error-message'>Une erreur s'est produite lors de la mise à jour du livre.</div>";
        echo "<p><a href='book_edit_form.php?id=" . $bookId . "'>Réessayer</a></p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error-message'>Erreur de base de données: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><a href='book_edit_form.php?id=" . $bookId . "'>Réessayer</a></p>";
}

include_once "./partials/bottom.php";
?>