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

    // Récupérer l'historique des emprunts pour ce livre
    $borrowStmt = $db->prepare("SELECT * FROM borrows WHERE book_id = ? ORDER BY borrow_date DESC");
    $borrowStmt->execute([$bookId]);
    $borrows = $borrowStmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='error-message'>Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><a href='book_index.php'>Retour à la liste des livres</a></p>";
    include_once "./partials/bottom.php";
    exit;
}
?>

<div class="book-details">
    <h2>Détails du livre</h2>
    
    <div class="book-header">
        <div class="book-cover">
            <?php if ($book['cover_path']): ?>
                <img src="<?= htmlspecialchars('../' . $book['cover_path']) ?>" alt="Couverture" style="max-width: 200px;">
            <?php else: ?>
                <div class="no-cover">Aucune couverture</div>
            <?php endif; ?>
        </div>
        
        <div class="book-info">
            <h3><?= htmlspecialchars($book['title']) ?></h3>
            <p><strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?></p>
            <p><strong>Année de publication:</strong> <?= htmlspecialchars($book['publication_year']) ?></p>
            <p><strong>Ajouté le:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($book['created_at']))) ?></p>
        </div>
    </div>
    
    <div class="book-summary">
        <h4>Résumé</h4>
        <p><?= $book['summary'] ? nl2br(htmlspecialchars($book['summary'])) : 'Aucun résumé disponible.' ?></p>
    </div>
    
    <div class="book-actions">
        <a href="book_edit_form.php?id=<?= $bookId ?>" class="btn">Modifier</a>
        <a href="book_delete_form.php?id=<?= $bookId ?>" class="btn btn-danger">Supprimer</a>
        <a href="book_index.php" class="btn btn-secondary">Retour à la liste</a>
    </div>
    
    <div class="borrow-history">
        <h4>Historique des emprunts</h4>
        <?php if (count($borrows) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Emprunteur</th>
                        <th>Date d'emprunt</th>
                        <th>Date de retour</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrows as $borrow): ?>
                    <tr>
                        <td><?= htmlspecialchars($borrow['borrower_name']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($borrow['borrow_date']))) ?></td>
                        <td>
                            <?= $borrow['return_date'] 
                                ? htmlspecialchars(date('d/m/Y', strtotime($borrow['return_date']))) 
                                : '—' ?>
                        </td>
                        <td>
                            <?= $borrow['return_date'] ? 'Retourné' : '<strong>En cours</strong>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Ce livre n'a jamais été emprunté.</p>
        <?php endif; ?>
    </div>
</div>

<?php
include_once "./partials/bottom.php";
?>