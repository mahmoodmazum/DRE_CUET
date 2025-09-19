<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();

$user = $_SESSION['user'];

// Validate params
$submission_id = $_GET['id'] ?? null;
$review_id = $_GET['review_id'] ?? null;

if (!$submission_id || !$review_id) {
    exit('Invalid request.');
}

// Check if this review belongs to logged-in reviewer
$stmt = $pdo->prepare("
    SELECT r.id AS review_id, s.project_title, u.name AS submitter_name
    FROM reviews r
    INNER JOIN submissions s ON r.submission_id = s.id
    INNER JOIN users u ON s.user_id = u.id
    INNER JOIN reviewer_pool rp ON r.reviewer_id = rp.id
    INNER JOIN users ru ON rp.user_id = ru.id
    WHERE r.id = ? AND ru.email = ?
");
$stmt->execute([$review_id, $user['email']]);
$review = $stmt->fetch();

if (!$review) {
    exit("You are not authorized to view this review.");
}

// Fetch existing bank info (if already submitted)
$stmt = $pdo->prepare("SELECT * FROM bank_info WHERE review_id = ? LIMIT 1");
$stmt->execute([$review_id]);
$bankInfo = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bankName = $_POST['bank_name'] ?? '';
    $accountNumber = $_POST['account_number'] ?? '';
    $routingNumber = $_POST['routing_number'] ?? '';
    $branchName = $_POST['branch_name'] ?? '';

    if ($bankInfo) {
        // Update
        $stmt = $pdo->prepare("UPDATE bank_info 
            SET bank_name = ?, account_number = ?, routing_number = ?, branch_name = ? 
            WHERE review_id = ?");
        $stmt->execute([$bankName, $accountNumber, $routingNumber, $branchName, $review_id]);
        $msg = "Bank info updated successfully.";
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO bank_info (review_id, bank_name, account_number, routing_number, branch_name) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$review_id, $bankName, $accountNumber, $routingNumber, $branchName]);
        $msg = "Bank info submitted successfully.";
    }

    header("Location: review_paper.php?msg=" . urlencode($msg));
    exit;
}

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <h1>Bank Info for Review #<?= htmlspecialchars($review['review_id']) ?></h1>
      <p><b>Project:</b> <?= htmlspecialchars($review['project_title']) ?></p>
      <p><b>Submitted By:</b> <?= htmlspecialchars($review['submitter_name']) ?></p>
    </div>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Bank Name</label>
            <input type="text" name="bank_name" class="form-control" required value="<?= htmlspecialchars($bankInfo['bank_name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Account Number</label>
            <input type="text" name="account_number" class="form-control" required value="<?= htmlspecialchars($bankInfo['account_number'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Routing Number</label>
            <input type="text" name="routing_number" class="form-control" required value="<?= htmlspecialchars($bankInfo['routing_number'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Branch Name</label>
            <input type="text" name="branch_name" class="form-control" required value="<?= htmlspecialchars($bankInfo['branch_name'] ?? '') ?>">
          </div>

          <button type="submit" class="btn btn-success">Save</button>
          <a href="review_paper.php" class="btn btn-secondary">Back</a>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
