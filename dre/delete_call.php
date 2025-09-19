<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

$id = $_POST['id'] ?? null;
if (!$id) exit('Invalid');
$pdo->prepare("DELETE FROM paper_calls WHERE id = ?")->execute([$id]);

header('Location: paper_calls.php?deleted=1');
exit;
