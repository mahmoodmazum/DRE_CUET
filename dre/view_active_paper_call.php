<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$paper_call_id = intval($_GET['id'] ?? 0);
if (!$paper_call_id) { exit('Proposal Call ID missing'); }

$callStmt = $pdo->prepare("SELECT * FROM paper_calls WHERE id = ?");
$callStmt->execute([$paper_call_id]);
$call = $callStmt->fetch();

$stmt = $pdo->prepare("
    SELECT s.*, d.name AS department_name, u.name AS pi_name
    FROM submissions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.paper_call_id = ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$paper_call_id]);
$submissions = $stmt->fetchAll();

$internalReviewers = $pdo->query("SELECT rp.id AS rp_id FROM reviewer_pool rp INNER JOIN users u ON rp.user_id = u.id WHERE rp.external_name IS NULL AND rp.external_email IS NULL")->fetchAll();
$externalReviewers = $pdo->query("SELECT id FROM reviewer_pool WHERE user_id IS NULL AND external_name IS NOT NULL")->fetchAll();
?>

<div class="main-content">
  <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
      <h1>Submitted Proposals</h1>
      <?php if ($call): ?>
        <div class="breadcrumb">
          Proposal Call — Issued: <?= htmlspecialchars($call['issue_date']) ?>
          &nbsp;|&nbsp; Deadline: <?= htmlspecialchars($call['deadline_date']) ?>
        </div>
      <?php endif; ?>
    </div>
    <a href="paper_calls.php" class="btn btn-secondary">
      <span class="material-icons-round">arrow_back</span> Back to Proposal Calls
    </a>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>Proposals (<?= count($submissions) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
      <table id="submissionsTable" class="data-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th>
            <th>Proposal Title</th>
            <th>Department</th>
            <th>Principal Investigator (PI)</th>
            <th>Total Cost</th>
            <th>Reviewer Assignment</th>
            <th>Review Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($submissions as $s):
            $sc = json_decode($s['staff_costs'], true) ?: [];
            $de = json_decode($s['direct_expenses'], true) ?: [];
            $totalCost = array_sum(array_map(function($r){ return (float)($r['year1']??$r['amount']??0)+(float)($r['year2']??0)+(float)($r['year3']??0); }, $sc))
                       + array_sum(array_map(function($r){ return (float)($r['year1']??$r['amount']??0)+(float)($r['year2']??0)+(float)($r['year3']??0); }, $de));

            $revStmt = $pdo->prepare("SELECT * FROM reviews WHERE submission_id = ?");
            $revStmt->execute([$s['id']]);
            $rev = $revStmt->fetchAll(PDO::FETCH_ASSOC);
            $assignedIds = array_column($rev, 'reviewer_id');

            $hasInternal = count(array_filter($internalReviewers, fn($t) => in_array($t['rp_id'], $assignedIds))) > 0;
            $hasExternal = count(array_filter($externalReviewers, fn($er) => in_array($er['id'], $assignedIds))) > 0;
          ?>
          <tr>
            <td><?= $s['id'] ?></td>
            <td style="font-weight:500;"><?= htmlspecialchars($s['project_title'] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['department_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['pi'] ?? '—') ?></td>
            <td style="font-weight:600;">৳ <?= number_format($totalCost, 2) ?></td>
            <td>
              <?php if ($hasInternal): ?>
                <span class="badge badge-primary">Internal</span>
              <?php endif; ?>
              <?php if ($hasExternal): ?>
                <span class="badge badge-success">External</span>
              <?php endif; ?>
              <?php if (!$hasInternal && !$hasExternal): ?>
                <span class="badge badge-secondary">None</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge badge-info"><?= htmlspecialchars($s['status'] ?? '—') ?></span>
            </td>
            <td>
              <a href="view_submission_admin.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">
                <span class="material-icons-round">visibility</span> View
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>
<script>
$(function(){
  $('#submissionsTable').DataTable({responsive:true});
});
</script>
