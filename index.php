<?php
// public/index.php
require __DIR__ . '/src/lib/Auth.php';
session_start();
if (empty($_SESSION['user'])) {
    header('Location: login.php'); exit;
}
$user = $_SESSION['user'];
$role = $user['role'] ?? '';
if ($role === 'dre_admin') header('Location: /DRE/dre/dashboard.php');
elseif ($role === 'teacher') header('Location: /DRE/teacher/dashboard.php');
else header('Location: /login.php');
