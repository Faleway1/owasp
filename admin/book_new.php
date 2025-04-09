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
$successes = [];

/**
 * ******************** [1] Check if submitted form is valid
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') { // Is method allowed ?
    if (isset($_POST['title']) && trim($_POST['title']) !== '') { // Required field value
        // OK
        $title = trim($_POST['title']);
        $titleLen = strlen($title);
        if ($titleLen < 2 || $titleLen > 150) { // Format check
            // KO
            $errors[] = "Le champ 'Titre' doit contenir entre 2 et 150 caractères.";
        }
    } else { // KO
        $errors[] = "Le champ 'Titre' est obligatoire. Merci de saisir une valeur.";
    }

    if (isset($_POST['isbn']) && trim($_POST['isbn']) !== '') { // Required field value
        $isbn = trim($_POST['isbn']);
        if (!preg_match($validPatterns['isbn'], $isbn)) { // Format check
            // KO
            $errors[] = "Le champ 'ISBN' doit contenir exactement 13 chiffres.";
        }
    } else { // KO
        $errors[] = "Le champ 'ISBN' est obligatoire. Merci de saisir une valeur.";
    }

    if (isset($_POST['summary']) && trim($_POST['summary']) !== '') { // Not required field value but essential test needed
        // OK
        $summary = trim($_POST['summary']);
        $summaryLen = strlen($summary);
        if ($summaryLen > 65535) { // Format check
            // KO
            $errors[] = "Le champ 'Résumé' doit contenir au plus 65535 caractères.";
        }
    } else {
        $summary = NULL;
    }

    if (isset($_POST['publication_year']) && trim($_POST['publication_year']) !== '') { // Required field value
        // OK
        $publicationYear = trim($_POST['publication_year']);
        if (!preg_match($validPatterns['year'], $publicationYear)) { // Format check
            // KO
            $errors[] = "Le champ 'Année de publication' doit être au format YYYY (ex. : 1997).";
        }
    } else { // KO
        $errors[] = "Le champ 'Année de publication' est obligatoire. Merci de saisir une valeur.";
    }
    
    // Gestion de l'upload de l'image
    $coverPath = null;
    if (!empty($_FILES['cover']['name'])) {
        $uploadResult = processImageUpload($_FILES['cover']);
        if ($uploadResult['success']) {
            $coverPath = $uploadResult['filepath'];
        } else {
            $errors[] = "Erreur lors de l'upload de l'image: " . $uploadResult['message'];
        }
    }

    if (count($errors) !== 0) {
        $errorMsg = "<ul class='error-message'>";
        foreach ($errors as $error) {
            $errorMsg .= "<li>$error</li>";
        }
        $errorMsg .= "</ul>";
        echo $errorMsg;
    } else {
        // Insertion en base de données
        try {
            $db = getDbConnection();
            
            // Vérifier si l'ISBN existe déjà
            $checkStmt = $db->prepare("SELECT id FROM books WHERE isbn = ?");
            $checkStmt->execute([$isbn]);
            
            if ($checkStmt->rowCount() > 0) {
                echo "<p class='error-message'>Un livre avec cet ISBN existe déjà dans la base de données.</p>";
            } else {
                // Préparation de la requête SQL avec ou sans image de couverture
                if ($coverPath) {
                    $stmt = $db->prepare("INSERT INTO books (title, isbn, summary, publication_year, cover_path) VALUES (?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$title, $isbn, $summary, $publicationYear, $coverPath]);
                } else {
                    $stmt = $db->prepare("INSERT INTO books (title, isbn, summary, publication_year) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$title, $isbn, $summary, $publicationYear]);
                }
                
                if ($result) {
                    echo "<p class='success-message'>Le livre a été ajouté avec succès !</p>";
                    echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
                } else {
                    echo "<p class='error-message'>Une erreur s'est produite lors de l'enregistrement du livre.</p>";
                }
            }
        } catch (PDOException $e) {
            echo "<p class='error-message'>Erreur de base de données: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} else { // KO
    // Traitement de l'erreur
    header('Location: ../405.php');
}

include_once "./partials/bottom.php";