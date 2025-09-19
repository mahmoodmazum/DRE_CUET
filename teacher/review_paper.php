<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];

// Find reviewer_pool id for this user
$stmt = $pdo->prepare("
    SELECT rp.id AS reviewer_pool_id 
    FROM users u
    INNER JOIN reviewer_pool rp ON u.id = rp.user_id
    WHERE u.email = ?
    LIMIT 1
");
$stmt->execute([$user['email']]);
$reviewer = $stmt->fetch();

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
include __DIR__ . '/../src/includes/sidebar_teacher.php';
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <h1>Assigned Reviews</h1>

      <?php if (!empty($_GET['msg'])): ?>
        <div class="alert alert-info alert-dismissible fade show mt-2" role="alert">
          <?= htmlspecialchars($_GET['msg']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

    </div>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">
        <?php if (!$reviews): ?>
            <p>No submissions assigned to you for review.</p>
        <?php else: ?>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Project Title</th>
                  <th>Submitted By</th>
                  <th>Deadline</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($reviews as $r): ?>
                  <tr>
                    <td><?= $r['submission_id'] ?></td>
                    <td><?= htmlspecialchars($r['project_title']) ?></td>
                    <td><?= htmlspecialchars($r['submitter_name']) ?></td>
                    <td><?= htmlspecialchars($r['deadline_date']) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td>
                      <a href="review_submission.php?id=<?= $r['submission_id'] ?>&review_id=<?= $r['review_id'] ?>" class="btn btn-sm btn-primary">Review</a>

                      <a href="bank_info.php?id=<?= $r['submission_id'] ?>&review_id=<?= $r['review_id'] ?>" class="btn btn-sm btn-info">Bank Info</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>


<?php include __DIR__ . '/../src/includes/footer.php'; ?>
