<?php
include_once "./utils/config.php";
include_once "./utils/auth.php";
include_once "./partials/top.php";

// Initialiser la session sécurisée
initSecureSession();

// Vérifier si un ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: 404.php');
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
        header('Location: 404.php');
        exit;
    }

    // Vérifier si le livre est actuellement emprunté
    $borrowStmt = $db->prepare("SELECT * FROM borrows WHERE book_id = ? AND return_date IS NULL");
    $borrowStmt->execute([$bookId]);
    $currentBorrow = $borrowStmt->fetch();
    
} catch (PDOException $e) {
    header('Location: 500.php');
    exit;
}
?>

<div class="container">
    <h2>Détails du livre</h2>
    
    <div class="book-container">
        <div class="book-header">
            <div class="book-cover">
                <?php if ($book['cover_path']): ?>
                    <img src="<?= htmlspecialchars($book['cover_path']) ?>" alt="Couverture" style="max-width: 200px;">
                <?php else: ?>
                    <div class="no-cover">Aucune couverture</div>
                <?php endif; ?>
            </div>
            
            <div class="book-info">
                <h3><?= htmlspecialchars($book['title']) ?></h3>
                <p><strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?></p>
                <p><strong>Année de publication:</strong> <?= htmlspecialchars($book['publication_year']) ?></p>
                <p>
                    <strong>Disponibilité:</strong> 
                    <?= $currentBorrow ? '<span class="unavailable">Actuellement emprunté</span>' : '<span class="available">Disponible</span>' ?>
                </p>
            </div>
        </div>
        
        <div class="book-summary">
            <h4>Résumé</h4>
            <p><?= $book['summary'] ? nl2br(htmlspecialchars($book['summary'])) : 'Aucun résumé disponible.' ?></p>
        </div>
        
        <div class="book-actions">
            <?php if (isAuthenticated()): ?>
                <?php if (!$currentBorrow): ?>
                    <a href="borrow_form.php?id=<?= $bookId ?>" class="btn btn-primary">Emprunter ce livre</a>
                <?php else: ?>
                    <p>Ce livre est actuellement indisponible.</p>
                <?php endif; ?>
            <?php else: ?>
                <p><a href="login.php">Connectez-vous</a> pour emprunter ce livre</p>
            <?php endif; ?>
            
            <a href="index.php" class="btn btn-secondary">Retour à la liste</a>
        </div>
    </div>
</div>

<?php
include_once "./partials/bottom.php";
?>