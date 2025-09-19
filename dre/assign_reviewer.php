<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];

if ($user['role'] !== 'dre_admin') {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request');
}

$submission_id        = $_POST['submission_id'] ?? null;
$internal_reviewer_id = $_POST['internal_reviewer_id'] ?? null; // reviewer_poll.id
$external_reviewer_id = $_POST['external_reviewer_id'] ?? null; // reviewer_poll.id
$comments = $_POST['comments'] ?? null; // reviewer_poll.id

if (!$submission_id) {
    exit('Submission ID missing');
}

try {
    $pdo->beginTransaction();

    // --- Internal Reviewer ---
    if ($internal_reviewer_id) {
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE submission_id = ? AND reviewer_id = ?");
        $stmt->execute([$submission_id, $internal_reviewer_id]);
        $exists = $stmt->fetch();

        if (!$exists) {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (submission_id, reviewer_id, comments, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$submission_id, $internal_reviewer_id,$comments]);
        }
    }

    // --- External Reviewer ---
    if ($external_reviewer_id) {
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE submission_id = ? AND reviewer_id = ?");
        $stmt->execute([$submission_id, $external_reviewer_id]);
        $exists = $stmt->fetch();

        if (!$exists) {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (submission_id, reviewer_id, comments, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$submission_id, $external_reviewer_id,$comments]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    exit("Error: " . $e->getMessage());
}

// Redirect back to submission view
header("Location: view_submission_admin.php?id=" . urlencode($submission_id));
exit;
