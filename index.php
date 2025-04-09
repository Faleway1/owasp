<?php
include_once "./utils/config.php";
include_once "./partials/top.php";

// Récupérer les 5 livres les plus récents depuis la base de données
$db = getDbConnection();
$query = "SELECT * FROM books ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recentBooks = $stmt->fetchAll();
?>
<h2>Médias les plus récents</h2>

<?php if (count($recentBooks) > 0): ?>
<div class="recent-books">
    <?php foreach($recentBooks as $book): ?>
    <div class="book-card">
        <?php if ($book['cover_path']): ?>
            <img src="<?= htmlspecialchars($book['cover_path']) ?>" alt="Couverture de <?= htmlspecialchars($book['title']) ?>" 
                 class="book-cover" style="max-width: 150px; max-height: 200px;">
        <?php endif; ?>
        <h3><?= htmlspecialchars($book['title']) ?></h3>
        <p class="book-year"><?= htmlspecialchars($book['publication_year']) ?></p>
        <p class="book-summary"><?= mb_strlen($book['summary']) > 150 ? htmlspecialchars(mb_substr($book['summary'], 0, 150)) . '...' : htmlspecialchars($book['summary']) ?></p>
        <a href="book_detail.php?id=<?= $book['id'] ?>">Voir plus</a>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<p>Aucun livre n'est disponible pour le moment.</p>
<?php endif; ?>

<p><a href="./admin/" title="Accès au dashboard">Accès au dashboard</a></p>
<?php
include_once "./partials/bottom.php";
?>