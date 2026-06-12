<?php
session_start();

// 1. Clear all session variables
$_SESSION = array();

// 2. If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session.
session_destroy();

// 4. Redirect to login page with a logout message
header("Location: ../index.php?msg=logged_out");
exit();
?>