<?php
session_start();
require __DIR__ . '/../src/db.php';

// Validate params
$submission_id = $_GET['id'] ?? null;
$review_id = $_GET['review_id'] ?? null;

if (!$submission_id || !$review_id) exit('Invalid request.');

// Check if this review belongs to logged-in reviewer
$stmt = $pdo->prepare("
    SELECT r.id AS review_id, s.project_title
    FROM reviews r
    INNER JOIN submissions s ON r.submission_id = s.id
    INNER JOIN reviewer_pool rp ON r.reviewer_id = rp.id
    WHERE r.id = ? AND rp.external_email = ?
");
$stmt->execute([$review_id, $_SESSION['reviewer_email']]);
$review = $stmt->fetch();
if (!$review) exit("You are not authorized to view this review.");

// Fetch existing bank info (if already submitted)
$stmt = $pdo->prepare("SELECT * FROM bank_info WHERE review_id = ? LIMIT 1");
$stmt->execute([$review_id]);
$bankInfo = $stmt->fetch();

// 8-color palette
$colors = [
    'primary' => '#1E88E5',
    'secondary' => '#D81B60',
    'success' => '#43A047',
    'info' => '#00ACC1',
    'warning' => '#FDD835',
    'error' => '#E53935',
    'light' => '#F5F5F5',
    'dark' => '#424242'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bankName = $_POST['bank_name'] ?? '';
    $accountNumber = $_POST['account_number'] ?? '';
    $routingNumber = $_POST['routing_number'] ?? '';
    $branchName = $_POST['branch_name'] ?? '';

    if ($bankInfo) {
        $stmt = $pdo->prepare("UPDATE bank_info 
            SET bank_name = ?, account_number = ?, routing_number = ?, branch_name = ? 
            WHERE review_id = ?");
        $stmt->execute([$bankName, $accountNumber, $routingNumber, $branchName, $review_id]);
        $msg = "Bank info updated successfully.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bank_info (review_id, bank_name, account_number, routing_number, branch_name) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$review_id, $bankName, $accountNumber, $routingNumber, $branchName]);
        $msg = "Bank info submitted successfully.";
    }

    header("Location: review_paper_external.php?msg=" . urlencode($msg));
    exit;
}

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_external.php';
?>

<div style="padding:24px; font-family: 'Roboto', sans-serif; background-color: <?= $colors['light'] ?>; min-height:100vh;">

    <!-- Header -->
    <div style="margin-bottom:24px;">
        <h2 style="color: <?= $colors['primary'] ?>; font-weight:500;">Bank Information</h2>
        <p><b>Project:</b> <?= htmlspecialchars($review['project_title']) ?></p>
    </div>

    <!-- Form Card -->
    <div style="background:white; padding:24px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); max-width:600px;">
        <form method="post">
            <div style="margin-bottom:16px;">
                <label style="font-weight:500; color: <?= $colors['dark'] ?>;">Bank Name</label>
                <input type="text" name="bank_name" required 
                    value="<?= htmlspecialchars($bankInfo['bank_name'] ?? '') ?>" 
                    style="width:100%; padding:10px; border:1px solid <?= $colors['dark'] ?>; border-radius:6px; margin-top:4px;">
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-weight:500; color: <?= $colors['dark'] ?>;">Account Number</label>
                <input type="text" name="account_number" required
                    value="<?= htmlspecialchars($bankInfo['account_number'] ?? '') ?>" 
                    style="width:100%; padding:10px; border:1px solid <?= $colors['dark'] ?>; border-radius:6px; margin-top:4px;">
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-weight:500; color: <?= $colors['dark'] ?>;">Routing Number</label>
                <input type="text" name="routing_number" required
                    value="<?= htmlspecialchars($bankInfo['routing_number'] ?? '') ?>" 
                    style="width:100%; padding:10px; border:1px solid <?= $colors['dark'] ?>; border-radius:6px; margin-top:4px;">
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-weight:500; color: <?= $colors['dark'] ?>;">Branch Name</label>
                <input type="text" name="branch_name" required
                    value="<?= htmlspecialchars($bankInfo['branch_name'] ?? '') ?>" 
                    style="width:100%; padding:10px; border:1px solid <?= $colors['dark'] ?>; border-radius:6px; margin-top:4px;">
            </div>

            <div style="display:flex; gap:12px; margin-top:16px;">
                <button type="submit" style="background-color: <?= $colors['success'] ?>; color:white; border:none; padding:12px 20px; border-radius:6px; font-weight:500; cursor:pointer;">
                    Save
                </button>
                <a href="review_paper.php" style="background-color: <?= $colors['secondary'] ?>; color:white; padding:12px 20px; border-radius:6px; text-decoration:none; font-weight:500;">Back</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
