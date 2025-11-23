<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
$submission_id=$_POST['submission_id'] ?? null;
$review_id = $_POST['review_id'] ?? null;
$marks = $_POST['marks'] ?? [];
$comments = $_POST['comments'] ?? [];

$stmt = $pdo->prepare("
    SELECT s.*
    FROM submissions s
    WHERE s.id = ?
");

$stmt->execute([$submission_id]);
$submission = $stmt->fetch();

if (!$review_id || empty($marks)) exit('Invalid submission.');

// // Validate marks and comments
// foreach ($marks as $idx => $mark) {
//     $mark = (float)$mark;
//     if (($mark > 0.8 * $_POST['allocated'][$idx] || $mark < 0.5 * $_POST['allocated'][$idx]) && empty(trim($comments[$idx]))) {
//         exit("Comment is required for criteria #".($idx+1)." because mark is greater than 80% or less than 50%.");
//     }
// }

// Save marks and comments
$pdo->beginTransaction();
try {

    //update submission table

    if ($submission['status']=='submitted'){$status='Internal';}
    elseif ($submission['status']=='External') {
        $status='Internal-External';
    }
    else{
        $status='submitted';
    }

    $stmt = $pdo->prepare("UPDATE submissions SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status , $submission_id]);

    // Delete existing marks if any
    $stmt = $pdo->prepare("DELETE FROM review_marks WHERE review_id = ?");
    $stmt->execute([$review_id]);

    // Insert new marks
    $stmt = $pdo->prepare("INSERT INTO review_marks (review_id, criterion_index, evaluated_marks, comment) VALUES (?, ?, ?, ?)");
    foreach ($marks as $idx => $mark) {
        $stmt->execute([$review_id, $idx, $mark, $comments[$idx]]);
    }

    // Optionally, update reviews table comments
    // $stmt = $pdo->prepare("UPDATE reviews SET comments = ?, updated_at = NOW() WHERE id = ?");
    // $stmt->execute([$comments , $review_id]);

    $pdo->commit();
    header("Location: review_paper.php?msg=Review submitted successfully");    
    
    
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    exit("Error saving review: ".$e->getMessage());
}
