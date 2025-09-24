<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';

// show active paper call if exists
$call = $pdo->query("SELECT * FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();
$submissions = $pdo->prepare("SELECT * FROM submissions WHERE user_id = ? ORDER BY created_at DESC");
$submissions->execute([$user['sub'] ?? $user['id']]);
$subs = $submissions->fetchAll();
?>

<!-- Material UI + Roboto + Icons -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />

<style>
  body {
    font-family: 'Roboto', sans-serif;
    background: #f5f5f5;
  }
  .content-wrapper {
    padding: 24px;
  }

  /* Alerts */
  .mui-alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    color: white;
  }

  /* Table Styling */
  .mui-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .mui-table thead {
    background: #1976d2;
    color: white;
  }
  .mui-table th, .mui-table td {
    padding: 12px 16px;
    text-align: left;
    font-size: 0.95rem;
  }
  .mui-table tbody tr:hover {
    background: rgba(25, 118, 210, 0.08);
  }

  /* 8-color alternating palette */
  .row-colored:nth-child(8n+1) { background: #e3f2fd; }
  .row-colored:nth-child(8n+2) { background: #e8f5e9; }
  .row-colored:nth-child(8n+3) { background: #fff3e0; }
  .row-colored:nth-child(8n+4) { background: #f3e5f5; }
  .row-colored:nth-child(8n+5) { background: #ede7f6; }
  .row-colored:nth-child(8n+6) { background: #fce4ec; }
  .row-colored:nth-child(8n+7) { background: #e0f7fa; }
  .row-colored:nth-child(8n+8) { background: #f1f8e9; }

  /* Buttons */
  .mui-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    text-decoration: none;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background 0.2s;
  }
  .mui-btn:hover { opacity: 0.9; }
</style>

<div class="content-wrapper">
  <?php if ($call): ?>
    <div class="mui-alert" style="background:#0288d1;">
      <strong>Active Paper Call</strong><br>
      Issue: <?= htmlspecialchars($call['issue_date']) ?> — Deadline: <?= htmlspecialchars($call['deadline_date']) ?><br>
      <?= nl2br(htmlspecialchars(substr($call['message'],0,300))) ?>
      <div style="margin-top:10px;">
        <a href="/DRE/teacher/submit_paper.php" class="mui-btn" style="background:#2e7d32;">
          <span class="material-icons" style="font-size:16px;">upload</span> Submit Paper
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="mui-alert" style="background:#f57c00;">
      No active paper call currently.
    </div>
  <?php endif; ?>

  <h3 style="margin-bottom:16px;">Your Submissions</h3>

  <table class="mui-table">
    <thead>
      <tr>
        <th style="width:60px;">ID</th>
        <th>Title</th>
        <th>PI</th>
        <th>Project Status</th>
        <th>Start Date</th>
        <th>Duration (Months)</th>
        <th style="width:120px;">Status</th>
        <th style="width:160px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($subs as $s): ?>
        <tr class="row-colored">
          <td><?= $s['id'] ?></td>
          <td><?= htmlspecialchars($s['project_title']) ?></td>
          <td><?= htmlspecialchars($s['pi']) ?></td>
          <td><?= htmlspecialchars($s['project_status']) ?></td>
          <td><?= htmlspecialchars($s['start_date']) ?></td>
          <td><?= htmlspecialchars($s['duration_months']) ?></td>
          
          <td><?= htmlspecialchars($s['status']) ?></td>
          <td>
            <?php if ($s['status'] === 'draft'): ?>
              <a href="/DRE/teacher/submit_paper.php?edit=<?= $s['id'] ?>" class="mui-btn">
                <span class="material-icons" style="font-size:16px;">edit</span> Edit
              </a>
            <?php endif; ?>
            <a href="/DRE/teacher/view_submission.php?id=<?= $s['id'] ?>" class="mui-btn" style="background:#7b1fa2;">
              <span class="material-icons" style="font-size:16px;">visibility</span> View
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
