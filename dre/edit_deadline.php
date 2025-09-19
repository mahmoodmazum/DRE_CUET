<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

$id = $_POST['id'] ?? null;
$deadline = $_POST['deadline_date'] ?? null;
$deadlineReview = $_POST['review_deadline'] ?? null;
if (!$id || !$deadline ) exit('Invalid');

$stmt = $pdo->prepare("UPDATE paper_calls SET deadline_date = ?, review_deadline=? WHERE id = ?");
$stmt->execute([$deadline,$deadlineReview ,$id]);

header('Location: paper_calls.php?updated=1');
exit;
