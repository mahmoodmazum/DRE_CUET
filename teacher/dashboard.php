<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/header.php';

//print_r();
include __DIR__ . '/../src/includes/sidebar_teacher.php';

// show active paper call if exists
$call = $pdo->query("SELECT * FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();
$submissions = $pdo->prepare("SELECT * FROM submissions WHERE user_id = ? ORDER BY created_at DESC");
$submissions->execute([$user['sub'] ?? $user['id']]);
$subs = $submissions->fetchAll();
?>

<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1>Dashboard</h1></div></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <?php if ($call): ?>
        <div class="alert alert-info">
          <strong>Active Paper Call</strong><br>
          Issue: <?= htmlspecialchars($call['issue_date']) ?> — Deadline: <?= htmlspecialchars($call['deadline_date']) ?><br>
          <?= nl2br(htmlspecialchars(substr($call['message'],0,300))) ?>
          <div class="mt-2"><a href="/DRE/teacher/submit_paper.php" class="btn btn-success btn-sm">Submit Paper</a></div>
        </div>
      <?php else: ?>
        <div class="alert alert-warning">No active paper call currently.</div>
      <?php endif; ?>

      <h5>Your Submissions</h5>
      <table class="table table-sm">
        <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($subs as $s): ?>
            <tr>
              <td><?= $s['id'] ?></td>
              <td><?= htmlspecialchars($s['project_title']) ?></td>
              <td><?= htmlspecialchars($s['status']) ?></td>
              <td>
                <?php if ($s['status'] === 'draft'): ?>
                  <a href="/DRE/teacher/submit_paper.php?edit=<?= $s['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <?php endif; ?>
                <a href="/DRE/teacher/view_submission.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </div></div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
