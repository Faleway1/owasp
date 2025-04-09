<?php

function startSecureSession() {
    $sessionName = 'MEDIATEK_SESSION';
    $secure = (ENVIRONMENT === 'production');
    $httponly = true;

    ini_set('session.use_only_cookies', 1);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    session_name($sessionName);

    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Lax'
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function createCsrfToken() {
    $token = bin2hex(random_bytes(32));
    try {
        $db = connectDb();
        $stmt = $db->prepare("INSERT INTO csrf_tokens (token, session_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt->execute([$token, session_id()]);
        $_SESSION['csrf_token'] = $token;
        return $token;
    } catch (PDOException $e) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}

function checkCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }

    try {
        $db = connectDb();
        $stmt = $db->prepare("SELECT token FROM csrf_tokens WHERE token = ? AND session_id = ? AND expires_at > NOW()");
        $stmt->execute([$token, session_id()]);
        return ($stmt->rowCount() > 0);
    } catch (PDOException $e) {
        return ($token === $_SESSION['csrf_token']);
    }
}

function purgeCsrfTokens() {
    try {
        $db = connectDb();
        $db->exec("DELETE FROM csrf_tokens WHERE expires_at < NOW()");
    } catch (PDOException $e) {}
}

function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isUserAdmin() {
    return isUserLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function enforceLogin() {
    if (!isUserLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }
}

function enforceAdmin() {
    if (!isUserAdmin()) {
        header('Location: /index.php?error=unauthorized');
        exit;
    }
}

function logFailedLogin($email) {
    try {
        $db = connectDb();
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1, last_login_attempt = NOW() WHERE email = ?");
        $stmt->execute([$email]);
        $stmt = $db->prepare("UPDATE users SET account_locked = TRUE WHERE email = ? AND failed_login_attempts >= 5");
        $stmt->execute([$email]);
    } catch (PDOException $e) {}
}

function checkAccountLock($email) {
    try {
        $db = connectDb();
        $stmt = $db->prepare("SELECT account_locked, last_login_attempt FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) return false;

        if ($user['account_locked']) {
            $lastAttempt = strtotime($user['last_login_attempt']);
            if (time() - $lastAttempt > 900) {
                $resetStmt = $db->prepare("UPDATE users SET account_locked = FALSE, failed_login_attempts = 0 WHERE email = ?");
                $resetStmt->execute([$email]);
                return false;
            }
            return true;
        }

        return false;
    } catch (PDOException $e) {
        return false;
    }
}

function clearLoginAttempts($email) {
    try {
        $db = connectDb();
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, account_locked = FALSE WHERE email = ?");
        $stmt->execute([$email]);
    } catch (PDOException $e) {}
}

function trackSuspiciousIp($ip) {
    try {
        $db = connectDb();
        $stmt = $db->prepare("SELECT * FROM ip_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);

        if ($stmt->rowCount() > 0) {
            $stmt = $db->prepare("UPDATE ip_attempts SET attempt_count = attempt_count + 1, last_attempt = NOW() WHERE ip_address = ?");
            $stmt->execute([$ip]);

            $stmt = $db->prepare("SELECT * FROM ip_attempts WHERE ip_address = ? AND attempt_count >= 10 AND TIMESTAMPDIFF(MINUTE, first_attempt, NOW()) <= 60");
            $stmt->execute([$ip]);

            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("UPDATE ip_attempts SET is_blocked = TRUE, block_expires = DATE_ADD(NOW(), INTERVAL 2 HOUR) WHERE ip_address = ?");
                $stmt->execute([$ip]);
                error_log("IP bloquée: $ip");
            }
        } else {
            $stmt = $db->prepare("INSERT INTO ip_attempts (ip_address) VALUES (?)");
            $stmt->execute([$ip]);
        }
    } catch (PDOException $e) {
        error_log("Erreur IP: " . $e->getMessage());
    }
}

function isIpBanned($ip) {
    try {
        $db = connectDb();
        $stmt = $db->prepare("SELECT * FROM ip_attempts WHERE ip_address = ? AND is_blocked = TRUE AND block_expires > NOW()");
        $stmt->execute([$ip]);
        return ($stmt->rowCount() > 0);
    } catch (PDOException $e) {
        error_log("Erreur blocage IP: " . $e->getMessage());
        return false;
    }
}

function clearIpAttempts($ip) {
    try {
        $db = connectDb();
        $stmt = $db->prepare("DELETE FROM ip_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
    } catch (PDOException $e) {
        error_log("Erreur reset IP: " . $e->getMessage());
    }
}
?>