<?php
include_once "../utils/config.php";
include_once "../utils/auth.php";

// Initialiser la session sécurisée
initSecureSession();

// Vérifier si l'utilisateur est un admin
if (!isAdmin()) {
    // Rediriger vers la page de connexion avec un message
    $_SESSION['error_message'] = "Accès restreint. Veuillez vous connecter avec un compte administrateur.";
    header('Location: ../auth/login.php');
    exit;
}

// Si tout va bien, afficher le tableau de bord
include_once "./partials/top.php";
?>

<h2>Tableau de bord d'administration</h2>

<div class="dashboard-stats">
    <div class="stat-card">
        <?php
        $db = getDbConnection();
        $bookCount = $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
        ?>
        <h3>Livres</h3>
        <p class="stat-number"><?= $bookCount ?></p>
        <a href="book_index.php" class="btn">Gérer les livres</a>
    </div>
    
    <div class="stat-card">
        <?php
        $borrowCount = $db->query("SELECT COUNT(*) FROM borrows WHERE return_date IS NULL")->fetchColumn();
        ?>
        <h3>Emprunts en cours</h3>
        <p class="stat-number"><?= $borrowCount ?></p>
        <a href="borrow_index.php" class="btn">Gérer les emprunts</a>
    </div>
    
    <div class="stat-card">
        <?php
        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        ?>
        <h3>Utilisateurs</h3>
        <p class="stat-number"><?= $userCount ?></p>
        <a href="user_index.php" class="btn">Gérer les utilisateurs</a>
    </div>
</div>

<p>
    <a href="../logout.php" class="btn btn-secondary">Se déconnecter</a>
</p>

<?php
include_once "./partials/bottom.php";
?>