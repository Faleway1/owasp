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
    
    // Vérifier si le livre est actuellement emprunté
    $borrowStmt = $db->prepare("SELECT * FROM borrows WHERE book_id = ? AND return_date IS NULL");
    $borrowStmt->execute([$bookId]);
    $isCurrentlyBorrowed = ($borrowStmt->rowCount() > 0);
    
} catch (PDOException $e) {
    echo "<div class='error-message'>Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
    include_once "./partials/bottom.php";
    exit;
}

// Générer un token CSRF pour le formulaire
$csrfToken = generateCsrfToken();
?>

<div class="delete-confirmation">
    <h2>Supprimer un livre</h2>
    
    <?php if ($isCurrentlyBorrowed): ?>
        <div class="warning-message">
            <strong>Attention!</strong> Ce livre est actuellement emprunté. La suppression n'est pas recommandée.
        </div>
    <?php endif; ?>
    
    <div class="book-info">
        <h3><?= htmlspecialchars($book['title']) ?></h3>
        <p><strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?></p>
        <p><strong>Année de publication:</strong> <?= htmlspecialchars($book['publication_year']) ?></p>
        
        <?php if ($book['cover_path']): ?>
            <div class="book-cover">
                <img src="<?= htmlspecialchars('../' . $book['cover_path']) ?>" alt="Couverture" style="max-width: 150px;">
            </div>
        <?php endif; ?>
    </div>
    
    <div class="confirmation-message">
        <p>Êtes-vous sûr de vouloir supprimer définitivement ce livre?</p>
        <p class="warning">Cette action est irréversible.</p>
    </div>
    
    <form action="book_delete.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= $bookId ?>">
        
        <div class="button-group">
            <input type="submit" name="confirm_delete" value="Oui, supprimer ce livre" class="btn-danger">
            <a href="book_show.php?id=<?= $bookId ?>" class="btn">Annuler</a>
        </div>
    </form>
</div>

<?php
include_once "./partials/bottom.php";
?>