<?php
require_once 'config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);

    $pdo->exec("CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        isbn VARCHAR(13) NOT NULL,
        summary TEXT,
        publication_year INT(4) NOT NULL,
        cover_path VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (isbn)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS borrows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_id INT NOT NULL,
        borrower_name VARCHAR(100) NOT NULL,
        borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        return_date TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        address TEXT,
        zip_code VARCHAR(10),
        birth_date DATE,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        failed_login_attempts INT DEFAULT 0,
        last_login_attempt TIMESTAMP NULL,
        account_locked BOOLEAN DEFAULT FALSE,
        email_verified BOOLEAN DEFAULT FALSE,
        verification_token VARCHAR(255) DEFAULT NULL,
        verification_expiry TIMESTAMP NULL,
        reset_token VARCHAR(255) DEFAULT NULL,
        reset_expiry TIMESTAMP NULL,
        UNIQUE KEY (email)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS csrf_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(255) NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        UNIQUE KEY (token)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ip_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        attempt_count INT DEFAULT 1,
        first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_blocked BOOLEAN DEFAULT FALSE,
        block_expires TIMESTAMP NULL,
        UNIQUE KEY (ip_address)
    )");

    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    if ($checkAdmin->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (email, password, last_name, first_name, is_admin) 
                   VALUES ('admin@mediatek.fr', '$hashedPassword', 'Admin', 'Super', 1)");
        echo "<p>Administrateur par défaut créé. Email: admin@mediatek.fr, Mot de passe: admin123</p>";
        echo "<p><strong>IMPORTANT: Changez ce mot de passe immédiatement après la première connexion!</strong></p>";
    }

    $books = [
        ['JavaScript - The Definitive Guide (7th ed.)', '9781491952023', 'Ce livre est une ressource essentielle pour tout développeur JavaScript, qu\'il soit débutant ou expérimenté. Il couvre en profondeur le langage JavaScript, son exécution dans les navigateurs et les environnements serveur. Cette édition met à jour les nouvelles fonctionnalités d\'ES6+, la programmation asynchrone, et les API modernes. C\'est un guide complet pour comprendre JavaScript de manière détaillée et avancée.', 2020],
        ['Python in a Nutshell (3rd ed.)', '9781449392925', 'Ce guide de référence offre une couverture détaillée de Python, allant des bases du langage aux concepts avancés. Il explore les bibliothèques standard, les meilleures pratiques de développement et les applications courantes en science des données, en automatisation et en développement web. Indispensable pour les développeurs cherchant une ressource concise et complète sur Python.', 2017],
        ['Learning React (2nd ed.)', '9781492051725', 'Un guide pratique pour comprendre React et son écosystème. Il explique les concepts fondamentaux tels que les composants, les hooks, le state management et le Virtual DOM. Cette édition inclut les nouvelles fonctionnalités de React, notamment les hooks et le suspense. Idéal pour les développeurs souhaitant maîtriser la création d\'interfaces dynamiques et réactives.', 2020],
        ['Fluent Python (2nd ed.)', '9781492056355', 'Un ouvrage avancé qui enseigne aux développeurs comment écrire un code Python efficace et idiomatique. Il couvre les structures de données, les classes, la programmation fonctionnelle et asynchrone. Cette seconde édition intègre les dernières évolutions du langage et propose des conseils pratiques pour améliorer la performance et la lisibilité du code.', 2022],
        ['Java: A Beginner\'s Guide (9th ed.)', '9781260463552', 'Ce livre est une introduction complète au langage Java, abordant les bases de la syntaxe, les structures de contrôle, la POO et les nouvelles fonctionnalités de Java 17. Chaque chapitre comprend des exercices pratiques pour renforcer l\'apprentissage. Une ressource précieuse pour les débutants souhaitant apprendre Java de manière progressive et efficace.', 2022],
        ['Introduction to Theoretical Computer Science (1st ed.)', '9780262042848', 'Une exploration approfondie des fondements de l\'informatique théorique, couvrant les automates, la complexité algorithmique, la logique computationnelle et la cryptographie. Ce livre fournit une base solide pour comprendre les principes fondamentaux des systèmes informatiques et des algorithmes.', 2022],
        ['Head First Design Patterns (2nd ed.)', '9781492078005', 'Cet ouvrage rend les design patterns accessibles grâce à une approche pédagogique et interactive. Il explique comment appliquer les patterns de conception pour rendre le code plus flexible, réutilisable et maintenable. Une ressource essentielle pour les développeurs souhaitant améliorer leurs compétences en conception logicielle.', 2020],
        ['Grokking Algorithms', '9781617292231', 'Un livre illustré et interactif qui explique les concepts clés des algorithmes de manière intuitive. Il aborde des notions comme le tri, la recherche, la récursivité et les graphes, en les rendant accessibles aux débutants. Idéal pour ceux qui veulent apprendre les algorithmes sans trop de formalismes mathématiques.', 2016],
        ['Large Scale Apps with Svelte and TypeScript', '9781801814232', 'Un guide détaillé sur le développement d\'applications évolutives avec Svelte et TypeScript. Il couvre la structuration du code, la gestion des états et les meilleures pratiques pour construire des applications performantes et maintenables.', 2023],
        ['Svelte Succinctly', '9781642002263', 'Un livre concis qui introduit Svelte, un framework JavaScript innovant. Il couvre les concepts fondamentaux comme les composants, la réactivité et la gestion des événements, permettant aux développeurs d\'exploiter pleinement Svelte pour créer des applications web modernes.', 2023],
        ['Python in a Nutshell (4th ed.)', '9781098113544', 'Une mise à jour du guide de référence sur Python, intégrant les dernières évolutions du langage et des bibliothèques. Ce livre est conçu pour être une ressource incontournable pour les développeurs souhaitant une maîtrise approfondie de Python.', 2023],
        ['Learning DevOps (2nd ed.)', '9781801819862', 'Une introduction aux concepts fondamentaux du DevOps, incluant l\'intégration et le déploiement continu, l\'automatisation des infrastructures et les conteneurs. Ce livre propose des études de cas et des exemples pratiques pour faciliter l\'apprentissage.', 2022],
        ['Cybersecurity for Dummies (2nd ed.)', '9781119867180', 'Un guide simple et accessible pour comprendre les principes de la cybersécurité, les menaces courantes et les meilleures pratiques de protection. Il couvre également les concepts de cryptographie, de gestion des identités et de prévention des attaques.', 2022],
        ['Unlock PHP 8: From Basic to Advanced', '9781801074537', 'Un livre qui couvre PHP 8 en profondeur, expliquant ses nouvelles fonctionnalités, ses performances améliorées et ses meilleures pratiques pour le développement web. Il offre un apprentissage progressif allant des bases aux concepts avancés.', 2024],
        ['Web Development with Node and Express (2nd ed.)', '9781492053507', 'Un guide pratique pour apprendre à construire des applications web performantes avec Node.js et Express. Il couvre l\'authentification, les bases de données, la gestion des sessions et les API REST.', 2019]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO books (title, isbn, summary, publication_year) VALUES (?, ?, ?, ?)");
    foreach ($books as $book) {
        $stmt->execute($book);
    }

    echo "<p>Base de données et tables créées avec succès!</p>";

} catch (PDOException $e) {
    die("<p>Erreur lors de l'initialisation de la base de données: " . $e->getMessage() . "</p>");
}
?>