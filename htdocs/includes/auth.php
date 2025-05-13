<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }


function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /index.php");
        exit();
    }
}

function require_role($role) {
    if (!is_logged_in() || $_SESSION['role'] !== $role) {
        header("Location: /index.php");
        exit();
    }
}

function require_any_role($roles = []) {
    if (!is_logged_in() || !in_array($_SESSION['role'], $roles)) {
        header("Location: /index.php");
        exit();
    }
}
?>
