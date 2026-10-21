<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function has_role($role) {
    $u = current_user();
    return $u && $u['role'] === $role;
}

function require_login($redirect = '/limedime/login.php') {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_role($roles, $redirect = '/limedime/index.php') {
    $u = current_user();
    if (!is_array($roles)) $roles = [$roles];
    if (!$u || !in_array($u['role'], $roles)) {
        header('Location: ' . $redirect);
        exit;
    }
}
