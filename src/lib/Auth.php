<?php
// src/lib/Auth.php
class Auth {
    public static function requireLogin() {
        session_start();
        if (empty($_SESSION['user'])) {
            header('Location: /login.php');
            exit;
        }
    }

    // convenience to get user
    public static function user() {
        session_start();
        return $_SESSION['user'] ?? null;
    }

    public static function logout() {
        session_start();
        $_SESSION = [];
        session_destroy();
    }
}
