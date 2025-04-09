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
?>

<h2>Ajouter un nouveau livre</h2>

<div class="form-container">
    <h4>Formulaire de création d'un livre</h4>
    <form action="book_new.php" method="POST" enctype="multipart/form-data" novalidate="">
        <div class="form-block">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" placeholder="Titre du livre" required="">
        </div>

        <div class="form-block">
            <label for="isbn">ISBN</label>
            <input type="text" id="isbn" name="isbn" placeholder="ISBN du livre" required="">
        </div>

        <div class="form-block">
            <label for="summary">Résumé</label>
            <textarea id="summary" name="summary" placeholder="Résumé du livre" rows="4"></textarea>
        </div>

        <div class="form-block">
            <label for="publication-year">Année de publication</label>
            <input type="number" id="publication-year" name="publication_year" placeholder="Année de publication (ex. : 2010)" min="1900" max="2025" step="1" value="" required="">
        </div>

        <div class="form-group">
            <label for="cover">Image de couverture (Formats acceptés: JPG, PNG, GIF, WEBP - Max: 5MB)</label>
            <input type="file" class="form-control" id="cover" name="cover" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>

        <input type="submit" name="book_new_submit" value="Enregistrer le nouveau livre">
    </form>
</div>
<?php
include_once "./partials/bottom.php";
?>