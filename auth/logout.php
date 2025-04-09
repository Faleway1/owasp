<?php
// Include config.php first since it contains the ENVIRONMENT constant
include_once "../utils/config.php";
include_once "../utils/auth.php";

// Initialize secure session
initSecureSession();

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../auth/login.php');
exit;
?>