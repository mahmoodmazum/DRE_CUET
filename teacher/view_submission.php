<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';

$id = $_GET['id'] ?? null;
if (!$id) { exit('Submission ID missing'); }

// Fetch submission
$stmt = $pdo->prepare("
    SELECT s.*, pc.deadline_date, d.name AS department_name
    FROM submissions s
    LEFT JOIN paper_calls pc ON s.paper_call_id = pc.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ? AND s.user_id = ?
");
$stmt->execute([$id, $user['sub'] ?? $user['id']]);
$submission = $stmt->fetch();

if (!$submission) { exit("Submission not found"); }

$canEditDelete = ($submission['deadline_date'] >= date('Y-m-d'));

// Decode JSON for costs
$staffCosts = json_decode($submission['staff_costs'], true) ?: [];
$directExpenses = json_decode($submission['direct_expenses'], true) ?: [];
$totalStaff = array_sum(array_column($staffCosts, 'amount'));
$totalDirect = array_sum(array_column($directExpenses, 'amount'));
$totalCost = $totalStaff + $totalDirect;

// Fetch attached files
$filesStmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ?");
$filesStmt->execute([$id]);
$attachments = $filesStmt->fetchAll();
?>

<!-- Material UI + Roboto -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
<script src="https://unpkg.com/@mui/material@5.15.14/umd/material-ui.development.js"></script>

<style>
  body {
    font-family: 'Roboto', sans-serif;
    background: #f5f5f5;
  }
  .content-wrapper {
    padding: 24px;
  }
  .card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  h1, h4 {
    margin-bottom: 16px;
    font-weight: 500;
  }
  .mui-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
  .mui-table th {
    background: #1976d2;
    color: white;
    padding: 12px;
    text-align: left;
    font-weight: 500;
  }
  .mui-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
  }
  .mui-table tbody tr:hover {
    background: rgba(25, 118, 210, 0.08);
  }
  /* 8-color palette for alternating rows */
  .row-colored:nth-child(8n+1) { background: #e3f2fd; }
  .row-colored:nth-child(8n+2) { background: #e8f5e9; }
  .row-colored:nth-child(8n+3) { background: #fff3e0; }
  .row-colored:nth-child(8n+4) { background: #f3e5f5; }
  .row-colored:nth-child(8n+5) { background: #ede7f6; }
  .row-colored:nth-child(8n+6) { background: #fce4ec; }
  .row-colored:nth-child(8n+7) { background: #e0f7fa; }
  .row-colored:nth-child(8n+8) { background: #f1f8e9; }
  .btn {
    display: inline-block;
    font-size: 0.9rem;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 6px;
    text-decoration: none;
    margin-right: 6px;
    color: white;
    transition: opacity 0.2s;
  }
  .btn:hover { opacity: 0.9; }
  .btn-primary { background: #1976d2; }
  .btn-danger { background: #d32f2f; }
  .btn-success { background: #2e7d32; }
  .btn-secondary { background: #616161; }


</style>



<div class="content-wrapper">
  <section class="content-header">
    <h1>Submission Details</h1>
  </section>

  <section class="content">
    <div class="card">

      <!-- Submission details -->
      <table class="mui-table">
        <tbody>
          <tr class="row-colored"><th style="width:30%;">Project Title</th><td><?= htmlspecialchars($submission['project_title']) ?></td></tr>
          <tr class="row-colored"><th>Status</th><td><?= htmlspecialchars($submission['status']) ?></td></tr>
          <tr class="row-colored"><th>Department</th><td><?= htmlspecialchars($submission['department_name'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Year</th><td><?= htmlspecialchars($submission['year'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Phase</th><td><?= htmlspecialchars($submission['phase'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Principal Investigator (PI)</th><td><?= htmlspecialchars($submission['pi'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Co-Principal Investigator (Co-PI)</th><td><?= htmlspecialchars($submission['co_pi'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Keywords</th><td><?= htmlspecialchars($submission['keywords'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Specific Objectives</th><td><?= nl2br(htmlspecialchars($submission['specific_objectives'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Research Background</th><td><?= nl2br(htmlspecialchars($submission['background'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Project Status</th><td><?= htmlspecialchars($submission['project_status'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Literature Review</th><td><?= nl2br(htmlspecialchars($submission['literature_review'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Related Research</th><td><?= nl2br(htmlspecialchars($submission['related_research'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Research Type</th><td><?= htmlspecialchars($submission['research_type'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Beneficiaries</th><td><?= nl2br(htmlspecialchars($submission['beneficiaries'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Outputs Expected</th><td><?= nl2br(htmlspecialchars($submission['outputs'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Technology Transfer</th><td><?= nl2br(htmlspecialchars($submission['transfer'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Organizational Outcomes</th><td><?= nl2br(htmlspecialchars($submission['organizational_outcomes'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>National Impacts</th><td><?= nl2br(htmlspecialchars($submission['national_impacts'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>External Organizations</th><td><?= nl2br(htmlspecialchars($submission['external_org'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored">
            <th>Project Team</th>
            <td>
              <?php 
                $team = json_decode($submission['project_team'], true);
                if ($team && is_array($team) && count($team) > 0): ?>
                  <table class="mui-table">
                    <thead>
                      <tr><th>Name</th><th>Organization</th><th>Man-Months</th></tr>
                    </thead>
                    <tbody>
                      <?php foreach ($team as $member): ?>
                        <tr>
                          <td><?= htmlspecialchars($member['name'] ?? '') ?></td>
                          <td><?= htmlspecialchars($member['org'] ?? '') ?></td>
                          <td><?= htmlspecialchars($member['mm'] ?? '') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php else: ?>
                  N/A
              <?php endif; ?>
            </td>
          </tr>
          <tr class="row-colored"><th>Research Methodology</th><td><?= nl2br(htmlspecialchars($submission['methodology'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Project Activities</th><td><?= nl2br(htmlspecialchars($submission['activities'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Key Milestones</th><td><?= nl2br(htmlspecialchars($submission['milestones'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Start Date</th><td><?= htmlspecialchars($submission['start_date'] ?? 'N/A') ?></td></tr>
          <tr class="row-colored"><th>Duration (Months)</th><td><?= htmlspecialchars($submission['duration_months'] ?? 'N/A') ?></td></tr>
        </tbody>
      </table>

      <!-- Staff Costs -->
      <h4>Staff Costs</h4>
      <table class="mui-table">
        <thead>
          <tr><th>Category</th><th>Year</th><th>Amount</th></tr>
        </thead>
        <tbody>
          <?php foreach ($staffCosts as $cost): ?>
            <tr>
              <td><?= htmlspecialchars($cost['category'] ?? '') ?></td>
              <td><?= htmlspecialchars($cost['year'] ?? '') ?></td>
              <td><?= $cost['amount'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th colspan="2">Total Staff Costs</th><th><?= number_format($totalStaff,2) ?></th></tr>
        </tfoot>
      </table>

      <!-- Direct Expenses -->
      <h4>Direct Expenses</h4>
      <table class="mui-table">
        <thead>
          <tr><th>Category</th><th>Year</th><th>Amount</th></tr>
        </thead>
        <tbody>
          <?php foreach ($directExpenses as $exp): ?>
            <tr>
              <td><?= htmlspecialchars($exp['category'] ?? '') ?></td>
              <td><?= htmlspecialchars($exp['year'] ?? '') ?></td>
              <td><?= number_format($exp['amount'] ?? 0,2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th colspan="2">Total Direct Expenses</th><th><?= number_format($totalDirect,2) ?></th></tr>
        </tfoot>
      </table>

      <h4>Total Cost: <?= number_format($totalCost,2) ?></h4>

      <!-- Other Details -->
      <h4>Other Details</h4>
      <table class="mui-table">
        <tbody>
          <tr class="row-colored"><th>Other Grants</th><td><?= nl2br(htmlspecialchars($submission['other_grants'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Contractual Obligations</th><td><?= nl2br(htmlspecialchars($submission['contractual_obligations'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>IP Ownership</th><td><?= nl2br(htmlspecialchars($submission['ip_ownership'] ?? 'N/A')) ?></td></tr>
          <tr class="row-colored"><th>Acknowledgement</th><td><?= $submission['acknowledgement'] ? 'Yes' : 'No' ?></td></tr>
        </tbody>
      </table>

      <!-- Uploaded Files -->
      <h4>Uploaded Files</h4>
      <?php
      $typeLabels = [
          'l_rev'    => 'Literature Review',
          'appendA'  => 'Appendix A',
          'appendB'  => 'Appendix B',
          'appendC'  => 'Appendix C'
      ];
      $attachmentsByType = [];
      foreach ($attachments as $file) {
          $attachmentsByType[$file['type']] = $file;
      }
      ?>
      <ul>
        <?php foreach ($typeLabels as $type => $label): ?>
          <li>
            <strong><?= $label ?>:</strong>
            <?php if (isset($attachmentsByType[$type])): 
              $file = $attachmentsByType[$type]; ?>
              <a href="/DRE/<?= htmlspecialchars($file['file_path']) ?>" target="_blank">
                <?= htmlspecialchars($file['original_name']) ?>
              </a>
              <small>(uploaded at <?= htmlspecialchars($file['uploaded_at']) ?>)</small>
            <?php else: ?>
              <em>Not uploaded</em>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Actions -->
      <div class="mt-3">
        <?php if ($canEditDelete): ?>
          <a href="submit_paper.php?edit=<?= $submission['id'] ?>" class="btn btn-primary">Edit</a>
          <a href="delete_submission.php?id=<?= $submission['id'] ?>" class="btn btn-danger"
             onclick="return confirm('Are you sure you want to delete this submission?');">Delete</a>
        <?php endif; ?>
        <a href="#" class="btn btn-success" id="printPageBtn">Print</a>

        <a href="dashboard.php" class="btn btn-secondary">Back</a>
      </div>

    </div>
  </section>
</div>

<script>
  document.getElementById('printPageBtn').addEventListener('click', function (e) {
    e.preventDefault();
    window.print();
  });
</script>


<?php include __DIR__ . '/../src/includes/footer.php'; ?>
