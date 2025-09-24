<?php
require __DIR__ . '/../src/db.php';

// Start session if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Reviewer email from GET
$reviewer_email = $_GET['email'] ?? null;
if (!$reviewer_email) {
    exit('No reviewer email provided.');
}

// Lookup reviewer in reviewer_pool
$stmt = $pdo->prepare("
    SELECT rp.id AS reviewer_pool_id
    FROM reviewer_pool rp
    WHERE rp.external_email = ?
    LIMIT 1
");
$stmt->execute([$reviewer_email]);
$reviewer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reviewer) {
    exit('You are not registered as a reviewer.');
}

// Store reviewer email in session
$_SESSION['reviewer_email'] = $reviewer_email;

//echo $_SESSION['reviewer_email'];

// Generate token
$secret = 'ihkgjygfyr7445'; // keep it secret
$expiry = time() + 3600; // valid 1 hour
$payload = json_encode([
    'email' => $reviewer_email,
    'expiry'=> $expiry
]);
$token = base64_encode($payload) . '.' . hash_hmac('sha256', $payload, $secret);

// Generate link
$link = "http://localhost:8081/DRE/teacher/review_paper_external.php?token=" . urlencode($token);

echo "Direct link for reviewer: <a href='$link'>$link</a>";
