<?php
session_start();
require __DIR__ . '/../src/db.php';


// Find reviewer_pool id for this user
$stmt = $pdo->prepare("
    SELECT rp.id AS reviewer_pool_id
    FROM reviewer_pool rp where 
    rp.external_email = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['reviewer_email']]);

$reviewer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reviewer) {
    exit('You are not registered as a reviewer.');
}

$reviewerPoolId = $reviewer['reviewer_pool_id'];

// Fetch reviews assigned to this reviewer where submission status is 'submitted'
$stmt = $pdo->prepare("
    SELECT r.id AS review_id, s.id AS submission_id, s.project_title, s.status, u.name AS submitter_name, pc.deadline_date
    FROM reviews r
    INNER JOIN submissions s ON r.submission_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN paper_calls pc ON s.paper_call_id = pc.id
    WHERE r.reviewer_id = ? AND (s.status = 'submitted' OR s.status = 'reviewed') AND pc.review_deadline >= CURDATE() 
    ORDER BY s.created_at DESC
");
$stmt->execute([$reviewerPoolId]);
$reviews = $stmt->fetchAll();

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_external.php';

// Define 8-color palette for table
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
?>

<div style="padding:24px; font-family: Roboto, sans-serif;">
  <h2 style="color: <?= $colors['primary'] ?>;">Assigned Reviews</h2>

  <?php if (!empty($_GET['msg'])): ?>
    <div style="background-color: <?= $colors['info'] ?>; color:white; padding:12px; margin-top:16px; border-radius:4px;">
      <?= htmlspecialchars($_GET['msg']) ?>
    </div>
  <?php endif; ?>

  <div style="margin-top:24px;">
    <?php if (!$reviews): ?>
        <p style="color: <?= $colors['dark'] ?>;">No submissions assigned to you for review.</p>
    <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
          <thead style="background-color: <?= $colors['light'] ?>;">
            <tr>
              <th style="padding:8px; border-bottom:2px solid <?= $colors['primary'] ?>; text-align:left;">#</th>
              <th style="padding:8px; border-bottom:2px solid <?= $colors['primary'] ?>; text-align:left;">Project Title</th>
              <th style="padding:8px; border-bottom:2px solid <?= $colors['primary'] ?>; text-align:left;">Deadline</th>
              <th style="padding:8px; border-bottom:2px solid <?= $colors['primary'] ?>; text-align:left;">Status</th>
              <th style="padding:8px; border-bottom:2px solid <?= $colors['primary'] ?>; text-align:left;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($reviews as $r): ?>
              <tr style="border-bottom:1px solid <?= $colors['light'] ?>;">
                <td style="padding:8px;"><?= $r['submission_id'] ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($r['project_title']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($r['deadline_date']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($r['status']) ?></td>
                <td style="padding:8px;">
                  <a href="review_submission_external.php?id=<?= $r['submission_id'] ?>&review_id=<?= $r['review_id'] ?>" 
                     style="background-color: <?= $colors['primary'] ?>; color:white; padding:6px 12px; border-radius:4px; text-decoration:none; margin-right:4px;">
                     Review
                  </a>

                  <a href="bank_info_external.php?id=<?= $r['submission_id'] ?>&review_id=<?= $r['review_id'] ?>" 
                     style="background-color: <?= $colors['secondary'] ?>; color:white; padding:6px 12px; border-radius:4px; text-decoration:none;">
                     Bank Info
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
