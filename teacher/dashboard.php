<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';

$call = $pdo->query("SELECT * FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();

$submissions = $pdo->prepare("SELECT s.*, d.name AS dept FROM submissions s LEFT JOIN departments d ON s.department_id=d.id WHERE s.user_id = ? ORDER BY s.created_at DESC");
$submissions->execute([$user['sub'] ?? $user['id']]);
$subs = $submissions->fetchAll();

$statusColors = [
    'submitted'       => 'badge-primary',
    'Internal'        => 'badge-warning',
    'External'        => 'badge-secondary',
    'Internal-External' => 'badge-success',
];
?>

<div class="main-content">
  <div class="page-header">
    <h1>Dashboard</h1>
    <div class="breadcrumb">Welcome, <?= htmlspecialchars($user['name']) ?></div>
  </div>

  <div class="page-body">

    <?php if (!empty($_GET['submitted'])): ?>
      <div class="alert alert-success">
        <span class="material-icons-round">check_circle</span>
        <span>Your proposal has been submitted successfully.</span>
      </div>
    <?php elseif (!empty($_GET['updated'])): ?>
      <div class="alert alert-success">
        <span class="material-icons-round">check_circle</span>
        <span>Your proposal has been updated successfully.</span>
      </div>
    <?php endif; ?>

    <?php if ($call): ?>
      <div class="alert alert-info" style="align-items:flex-start;flex-direction:column;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;font-weight:600;">
          <span class="material-icons-round">campaign</span>
          Active Call For Proposal
        </div>
        <div style="font-size:0.88rem;line-height:1.6;">
          <strong>Issue Date:</strong> <?= htmlspecialchars($call['issue_date']) ?> &nbsp;|&nbsp;
          <strong>Submission Deadline:</strong> <?= htmlspecialchars($call['deadline_date']) ?>
          <?php if ($call['review_deadline']): ?>
            &nbsp;|&nbsp; <strong>Review Deadline:</strong> <?= htmlspecialchars($call['review_deadline']) ?>
          <?php endif; ?>
        </div>
        <?php if ($call['message']): ?>
          <div style="font-size:0.85rem;color:var(--c-primary-dark);opacity:.9;">
            <?= nl2br(htmlspecialchars(substr($call['message'],0,300))) ?>
          </div>
        <?php endif; ?>
        <a href="/DRE/teacher/submit_paper.php" class="btn btn-primary">
          <span class="material-icons-round">upload_file</span> Submit Proposal
        </a>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">
        <span class="material-icons-round">info</span>
        <span>There is no active Call For Proposal at this time.</span>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h3>Your Submitted Proposals</h3>
        <?php if ($call): ?>
          <a href="/DRE/teacher/submit_paper.php" class="btn btn-primary btn-sm">
            <span class="material-icons-round">add</span> New Proposal
          </a>
        <?php endif; ?>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($subs)): ?>
          <div style="padding:40px;text-align:center;color:var(--c-text-muted);">
            <span class="material-icons-round" style="font-size:40px;opacity:.3;">description</span>
            <p style="margin-top:8px;">No proposals submitted yet.</p>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Project Title</th>
                  <th>PI</th>
                  <th>Department</th>
                  <th>Project Status</th>
                  <th>Submission Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($subs as $s): ?>
                  <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= htmlspecialchars($s['project_title'] ?? '—') ?></td>
                    <td style="font-size:0.82rem;"><?= htmlspecialchars($s['pi'] ?? '—') ?></td>
                    <td style="font-size:0.82rem;"><?= htmlspecialchars($s['dept'] ?? '—') ?></td>
                    <td><span class="badge badge-secondary"><?= htmlspecialchars($s['project_status'] ?? '—') ?></span></td>
                    <td><span class="badge <?= $statusColors[$s['status']] ?? 'badge-secondary' ?>"><?= htmlspecialchars($s['status'] ?? '—') ?></span></td>
                    <td>
                      <div class="btn-group">
                        <a href="/DRE/teacher/view_submission.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">
                          <span class="material-icons-round">visibility</span> View
                        </a>
                        <?php if ($call && $s['paper_call_id'] == $call['id']): ?>
                          <a href="/DRE/teacher/submit_paper.php?edit=<?= $s['id'] ?>" class="btn btn-outline btn-sm">
                            <span class="material-icons-round">edit</span> Edit
                          </a>
                          <a href="/DRE/teacher/delete_submission.php?id=<?= $s['id'] ?>"
                             class="btn btn-danger btn-sm"
                             onclick="return confirm('Delete this proposal?')">
                            <span class="material-icons-round">delete</span>
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
