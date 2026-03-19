<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];

$stmt = $pdo->prepare("
    SELECT rp.id AS reviewer_pool_id
    FROM users u
    INNER JOIN reviewer_pool rp ON u.id = rp.user_id
    WHERE u.email = ?
    LIMIT 1
");
$stmt->execute([$user['email']]);
$reviewer = $stmt->fetch();
if (!$reviewer) { exit('You are not registered as a reviewer.'); }

$reviewerPoolId = $reviewer['reviewer_pool_id'];

$stmt = $pdo->prepare("
    SELECT r.id AS review_id, s.id AS submission_id, s.project_title, s.status,
           u.name AS submitter_name, pc.deadline_date, pc.review_deadline
    FROM reviews r
    INNER JOIN submissions s ON r.submission_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN paper_calls pc ON s.paper_call_id = pc.id
    WHERE r.reviewer_id = ? AND pc.review_deadline >= CURDATE()
    ORDER BY s.created_at DESC
");
$stmt->execute([$reviewerPoolId]);
$reviews = $stmt->fetchAll();

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';
?>

<div class="main-content">
  <div class="page-header">
    <h1>Review Proposal</h1>
    <div class="breadcrumb">Proposals assigned to you for review</div>
  </div>

  <div class="page-body">

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-success">
        <span class="material-icons-round">check_circle</span>
        <span><?= htmlspecialchars($_GET['msg']) ?></span>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h3>Assigned Proposals</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if (!$reviews): ?>
          <div style="padding:40px;text-align:center;color:var(--c-text-muted);">
            <span class="material-icons-round" style="font-size:40px;opacity:.3;">rate_review</span>
            <p style="margin-top:8px;">No proposals are currently assigned to you for review.</p>
          </div>
        <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project Title</th>
                <th>Review Deadline</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($reviews as $r): ?>
                <tr>
                  <td><?= $r['submission_id'] ?></td>
                  <td><?= htmlspecialchars($r['project_title']) ?></td>
                  <td><?= htmlspecialchars($r['review_deadline'] ?? $r['deadline_date']) ?></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($r['status']) ?></span></td>
                  <td>
                    <div class="btn-group">
                      <a href="review_submission.php?id=<?= $r['submission_id'] ?>&review_id=<?= $r['review_id'] ?>"
                         class="btn btn-primary btn-sm">
                        <span class="material-icons-round">rate_review</span> Review
                      </a>
                      <a href="bank_info.php?id=<?= $r['submission_id'] ?>&review_id=<?= $r['review_id'] ?>"
                         class="btn btn-secondary btn-sm">
                        <span class="material-icons-round">account_balance</span> Bank Info
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
