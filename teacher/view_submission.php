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

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid"><h1>Submission Details</h1></div>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">

        <!-- Existing submission details table -->
        <table class="table table-bordered">
          <tbody>
            <tr><th style="width:30%;">Project Title</th><td><?= htmlspecialchars($submission['project_title']) ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($submission['status']) ?></td></tr>
            <tr><th>Department</th><td><?= htmlspecialchars($submission['department_name'] ?? 'N/A') ?></td></tr>
            <tr><th>Year</th><td><?= htmlspecialchars($submission['year'] ?? 'N/A') ?></td></tr>
            <tr><th>Phase</th><td><?= htmlspecialchars($submission['phase'] ?? 'N/A') ?></td></tr>
            <tr><th>Principal Investigator (PI)</th><td><?= htmlspecialchars($submission['pi'] ?? 'N/A') ?></td></tr>
            <tr><th>Co-Principal Investigator (Co-PI)</th><td><?= htmlspecialchars($submission['co_pi'] ?? 'N/A') ?></td></tr>
            <tr><th>Keywords</th><td><?= htmlspecialchars($submission['keywords'] ?? 'N/A') ?></td></tr>
            <tr><th>Specific Objectives</th><td><?= nl2br(htmlspecialchars($submission['specific_objectives'] ?? 'N/A')) ?></td></tr>
            <tr><th>Research Background</th><td><?= nl2br(htmlspecialchars($submission['background'] ?? 'N/A')) ?></td></tr>
            <tr><th>Project Status</th><td><?= htmlspecialchars($submission['project_status'] ?? 'N/A') ?></td></tr>
            <tr><th>Literature Review</th><td><?= nl2br(htmlspecialchars($submission['literature_review'] ?? 'N/A')) ?></td></tr>
            <tr><th>Related Research</th><td><?= nl2br(htmlspecialchars($submission['related_research'] ?? 'N/A')) ?></td></tr>
            <tr><th>Research Type</th><td><?= htmlspecialchars($submission['research_type'] ?? 'N/A') ?></td></tr>
            <tr><th>Beneficiaries</th><td><?= nl2br(htmlspecialchars($submission['beneficiaries'] ?? 'N/A')) ?></td></tr>
            <tr><th>Outputs Expected</th><td><?= nl2br(htmlspecialchars($submission['outputs'] ?? 'N/A')) ?></td></tr>
            <tr><th>Technology Transfer</th><td><?= nl2br(htmlspecialchars($submission['transfer'] ?? 'N/A')) ?></td></tr>
            <tr><th>Organizational Outcomes</th><td><?= nl2br(htmlspecialchars($submission['organizational_outcomes'] ?? 'N/A')) ?></td></tr>
            <tr><th>National Impacts</th><td><?= nl2br(htmlspecialchars($submission['national_impacts'] ?? 'N/A')) ?></td></tr>
            <tr><th>External Organizations</th><td><?= nl2br(htmlspecialchars($submission['external_org'] ?? 'N/A')) ?></td></tr>
            <tr>
              <th>Project Team</th>
              <td>
                <?php 
                  $team = json_decode($submission['project_team'], true);
                  if ($team && is_array($team) && count($team) > 0): ?>
                    <table class="table table-bordered table-sm mb-0">
                      <thead>
                        <tr>
                          <th>Name</th>
                          <th>Organization</th>
                          <th>Man-Months</th>
                        </tr>
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
            <tr><th>Research Methodology</th><td><?= nl2br(htmlspecialchars($submission['methodology'] ?? 'N/A')) ?></td></tr>
            <tr><th>Project Activities</th><td><?= nl2br(htmlspecialchars($submission['activities'] ?? 'N/A')) ?></td></tr>
            <tr><th>Key Milestones</th><td><?= nl2br(htmlspecialchars($submission['milestones'] ?? 'N/A')) ?></td></tr>
            <tr><th>Start Date</th><td><?= htmlspecialchars($submission['start_date'] ?? 'N/A') ?></td></tr>
            <tr><th>Duration (Months)</th><td><?= htmlspecialchars($submission['duration_months'] ?? 'N/A') ?></td></tr>
          </tbody>
        </table>

        <!-- Staff Costs Table -->
        <h4>Staff Costs</h4>
        <table class="table table-bordered table-sm">
          <thead class="thead-dark">
            <tr><th>Category</th><th>Year</th><th>Amount</th></tr>
          </thead>
          <tbody>
            <?php foreach ($staffCosts as $cost): ?>
              <tr>
                <td><?= htmlspecialchars($cost['category'] ?? '') ?></td>
                <td><?= htmlspecialchars($cost['year'] ?? '') ?></td>
                <td><?= number_format($cost['amount'] ?? 0,2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="2">Total Staff Costs</th><th><?= number_format($totalStaff,2) ?></th></tr>
          </tfoot>
        </table>

        <!-- Direct Expenses Table -->
        <h4>Direct Expenses</h4>
        <table class="table table-bordered table-sm">
          <thead class="thead-dark">
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

        <h4>Other Details</h4>
        <table class="table table-bordered">
          <tbody>
            <tr><th>Other Grants</th><td><?= nl2br(htmlspecialchars($submission['other_grants'] ?? 'N/A')) ?></td></tr>
            <tr><th>Contractual Obligations</th><td><?= nl2br(htmlspecialchars($submission['contractual_obligations'] ?? 'N/A')) ?></td></tr>
            <tr><th>IP Ownership</th><td><?= nl2br(htmlspecialchars($submission['ip_ownership'] ?? 'N/A')) ?></td></tr>
            <tr><th>Acknowledgement</th><td><?= $submission['acknowledgement'] ? 'Yes' : 'No' ?></td></tr>
          </tbody>
        </table>

        <!-- Uploaded Files Section -->
        <h4>Uploaded Files</h4>

<?php
$typeLabels = [
    'l_rev'    => 'Literature Review',
    'appendA'  => 'Appendix A',
    'appendB'  => 'Appendix B',
    'appendC'  => 'Appendix C'
];

// Group attachments by type
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
            <a href="/<?= htmlspecialchars($file['file_path']) ?>" target="_blank">
                <?= htmlspecialchars($file['original_name']) ?>
            </a>
            <small>(uploaded at <?= htmlspecialchars($file['uploaded_at']) ?>)</small>
        <?php else: ?>
            <em>Not uploaded</em>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>


        <div class="mt-3">
          <?php if ($canEditDelete): ?>
            <a href="submit_paper.php?edit=<?= $submission['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
            <a href="delete_submission.php?id=<?= $submission['id'] ?>" class="btn btn-danger btn-sm"
               onclick="return confirm('Are you sure you want to delete this submission?');">Delete</a>
          <?php endif; ?>
          <a href="#" class="btn btn-success btn-sm">Print</a>
          <a href="dashboard.php" class="btn btn-secondary btn-sm">Back</a>
        </div>

      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
