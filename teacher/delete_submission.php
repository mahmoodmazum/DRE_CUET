<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

$id = $_GET['id'] ?? null;
if (!$id) { exit('Submission ID missing'); }

$stmt = $pdo->prepare("SELECT s.*, pc.deadline_date 
                       FROM submissions s
                       LEFT JOIN paper_calls pc ON s.paper_call_id  = pc.id
                       WHERE s.id = ? AND s.user_id = ?");
$stmt->execute([$id, $user['sub'] ?? $user['id']]);
$submission = $stmt->fetch();

if (!$submission) { exit("Submission not found"); }

if ($submission['deadline_date'] < date('Y-m-d')) {
    exit("Error: Deadline passed. Cannot delete.");
}

// Delete file if exists
if (!empty($submission['file_path']) && file_exists(__DIR__ . '/../' . $submission['file_path'])) {
    unlink(__DIR__ . '/../' . $submission['file_path']);
}

// Delete record
$stmt = $pdo->prepare("DELETE FROM submissions WHERE id = ?");
$stmt->execute([$id]);

header("Location: dashboard.php?msg=deleted");
exit;
