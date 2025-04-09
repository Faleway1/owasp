<?php
include_once "../utils/config.php";
include_once "./partials/top.php";
include_once "../utils/auth.php";
include_once "../utils/regex.php";

// Initialiser la session sécurisée
initSecureSession();

// Vérifier si l'utilisateur est un admin
if (!isAdmin()) {
    // Rediriger vers la page de connexion avec un message
    $_SESSION['error_message'] = "Accès restreint. Veuillez vous connecter avec un compte administrateur.";
    header('Location: ../login.php');
    exit;
}

// Vérifier si un ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='error-message'>ID de livre invalide.</div>";
    echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
    include_once "./partials/bottom.php";
    exit;
}

$bookId = intval($_GET['id']);

// Récupérer les détails du livre
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();

    if (!$book) {
        echo "<div class='error-message'>Livre non trouvé.</div>";
        echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
        include_once "./partials/bottom.php";
        exit;
    }
} catch (PDOException $e) {
    echo "<div class='error-message'>Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
    include_once "./partials/bottom.php";
    exit;
}

// Générer un token CSRF pour le formulaire
$csrfToken = generateCsrfToken();
?>

<h2>Modifier un livre</h2>

<div class="form-container">
    <h4>Formulaire de modification du livre</h4>
    <form action="book_edit.php" method="POST" enctype="multipart/form-data" novalidate="">
        <!-- Token CSRF caché -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <!-- ID du livre caché -->
        <input type="hidden" name="id" value="<?= $bookId ?>">
        
        <div class="form-block">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" placeholder="Titre du livre" required 
                   value="<?= htmlspecialchars($book['title']) ?>">
        </div>

        <div class="form-block">
            <label for="isbn">ISBN</label>
            <input type="text" id="isbn" name="isbn" placeholder="ISBN du livre" required 
                   value="<?= htmlspecialchars($book['isbn']) ?>">
        </div>

        <div class="form-block">
            <label for="summary">Résumé</label>
            <textarea id="summary" name="summary" placeholder="Résumé du livre" rows="4"><?= htmlspecialchars($book['summary']) ?></textarea>
        </div>

        <div class="form-block">
            <label for="publication-year">Année de publication</label>
            <input type="number" id="publication-year" name="publication_year" 
                   placeholder="Année de publication (ex. : 2010)" min="1900" max="2025" step="1" 
                   value="<?= htmlspecialchars($book['publication_year']) ?>" required>
        </div>

        <div class="form-group">
            <label for="cover">Image de couverture (Formats acceptés: JPG, PNG, GIF, WEBP - Max: 5MB)</label>
            <?php if ($book['cover_path']): ?>
                <div class="current-cover">
                    <p>Couverture actuelle:</p>
                    <img src="<?= htmlspecialchars('../' . $book['cover_path']) ?>" alt="Couverture actuelle" style="max-width: 150px; max-height: 200px;">
                    <label><input type="checkbox" name="remove_cover"> Supprimer l'image actuelle</label>
                </div>
            <?php endif; ?>
            <input type="file" class="form-control" id="cover" name="cover" accept="image/jpeg,image/png,image/gif,image/webp">
            <small>Laisser vide pour conserver l'image actuelle</small>
        </div>

        <div class="button-row">
            <input type="submit" name="book_edit_submit" value="Enregistrer les modifications">
            <a href="book_show.php?id=<?= $bookId ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php
include_once "./partials/bottom.php";
?>